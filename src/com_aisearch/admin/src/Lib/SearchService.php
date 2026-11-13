<?php
declare(strict_types=1);
namespace Colorpack\Component\AiSearch\Administrator\Lib;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

final class SearchService
{
    private const QUERY_STOP = ['ยัง', 'ไง', 'ทํา', 'ให้', 'ไหม', 'อะไร', 'ดี', 'เมื่อ', 'ทําไง', 'อย่างไร', 'ควร', 'ต้อง'];

    public function __construct(private DatabaseInterface $db, private Settings $settings) {}

    private ?array $synMap = null;

    /** phrase => alternatives, from the synonym rules (or the old text box until the rules have been moved in) */
    private function synonyms(): array
    {
        return $this->synMap ??= (new SynonymStore($this->db, $this->settings))->map();
    }

    /**
     * A one-word search ("นมแม่") is too short to say what the visitor wants, so articles that only mention the word are noise:
     * show the articles whose TITLE has the word (then those whose title has a synonym of it), newest or most read first.
     * Returns null when this does not apply (the setting is off, more than one word, a rule that explains the wish, or no title has the word),
     * and the normal search runs instead.
     * @return array{ids:int[],info:array}|null
     */
    private function titleMatches(array $units, array $concepts, array $fired, ThaiTokenizer $tk, Bm25 $bm): ?array
    {
        if ($this->settings->shortQueryMode() === 0 || count($units) !== 1 || $concepts || mb_strlen($units[0]) < 2 || ctype_digit($units[0])) {
            return null;
        }
        $db = $this->db;
        $exact = $tk->normalize($units[0]);
        $syn = [];
        foreach ($fired as $key => $alts) {
            if (!str_contains((string) $key, '+') && mb_strpos($tk->normalize((string) $key), $exact) !== false) {
                foreach ($alts as $a) { $syn[] = $tk->normalize((string) $a); }
            }
        }
        $syn = array_values(array_diff(array_unique(array_filter($syn)), [$exact]));
        $likes = [];
        foreach (array_merge([$exact], $syn) as $f) {
            $likes[] = $db->quoteName('title') . " LIKE '%" . $db->escape($f, true) . "%'";
        }
        try {
            $rows = $db->setQuery('SELECT id, title, created FROM ' . $db->quoteName('#__aisearch_docs') . ' WHERE (' . implode(' OR ', $likes) . ') AND ' . $this->visibleWhere() . ' LIMIT 3000')->loadObjectList() ?: [];
        } catch (\Throwable $e) {
            return null;
        }
        $a = []; $b = [];
        foreach ($rows as $r) {
            $t = $tk->normalize((string) $r->title);
            if (mb_strpos($t, $exact) !== false) {
                $a[(int) $r->id] = $r;
            } else {
                foreach ($syn as $f) {
                    if (mb_strpos($t, $f) !== false) { $b[(int) $r->id] = $r; break; }
                }
            }
        }
        if (!$a && !$b) {
            return null;
        }
        $hits = [];
        $all = array_keys($a + $b);
        try {
            foreach ($db->setQuery('SELECT id, hits FROM ' . $db->quoteName('#__content') . ' WHERE id IN (' . implode(',', array_map('intval', $all)) . ')')->loadObjectList() ?: [] as $h) { $hits[(int) $h->id] = (int) $h->hits; }
        } catch (\Throwable $e) {
        }
        $popular = $this->settings->shortQueryMode() === 2;
        // the article that has the word in the most fields (title, tags, keywords, description, opening) comes first; then newest or most read
        try {
            $cover = (new Precision($db, $tk, $bm, $fired, $this->settings->precisionCommonPct(), $this->settings->precisionMaxTier(), 0))->coverageOf($units, $all);
        } catch (\Throwable $e) {
            $cover = [];
        }
        $sort = static function (array $set) use ($hits, $popular, $cover): array {
            $ids = array_keys($set);
            usort($ids, static function ($x, $y) use ($set, $hits, $popular, $cover) {
                $dx = (string) $set[$x]->created; $dy = (string) $set[$y]->created; $hx = $hits[$x] ?? 0; $hy = $hits[$y] ?? 0;
                $cx = $cover[$x] ?? 0; $cy = $cover[$y] ?? 0;
                return $popular ? [-$cx, $hy, $dy] <=> [-$cy, $hx, $dx] : [-$cx, $dy, $hy] <=> [-$cy, $dx, $hx];
            });
            return $ids;
        };
        $info = [];
        $ids = [];
        foreach ($sort($a) as $id) { $ids[] = $id; $info[$id] = ['tier' => 1, 'why' => 'the word is in the title', 'context' => true, 'fields' => $cover[$id] ?? 0]; }
        foreach ($sort($b) as $id) { $ids[] = $id; $info[$id] = ['tier' => 2, 'why' => 'a synonym is in the title', 'context' => true]; }
        return ['ids' => $ids, 'info' => $info];
    }

    /**
     * SQL conditions that hide articles which are not visible to this visitor right now: not yet published, already expired,
     * or in an access level the visitor does not have. Checked at search time, so nothing has to run when a date arrives.
     */
    private function visibleWhere(): string
    {
        $db = $this->db;
        $now = $db->quote(gmdate('Y-m-d H:i:s'));
        try {
            $user = \Joomla\CMS\Factory::getApplication()->getIdentity();
            $levels = $user ? array_map('intval', $user->getAuthorisedViewLevels()) : [1];
        } catch (\Throwable $e) {
            $levels = [1];
        }
        $levels = $levels ?: [1];
        $in = implode(',', $levels);
        $cat = $db->quoteName('#__categories');
        // the article's category, and every category above it, must be published and open to this visitor
        $where = '(' . $db->quoteName('publish_up') . ' IS NULL OR ' . $db->quoteName('publish_up') . ' <= ' . $now . ')'
            . ' AND (' . $db->quoteName('publish_down') . ' IS NULL OR ' . $db->quoteName('publish_down') . ' > ' . $now . ')'
            . ' AND ' . $db->quoteName('access') . ' IN (' . $in . ')'
            . ' AND ' . $db->quoteName('catid') . ' IN (SELECT c.id FROM ' . $cat . " c WHERE c.extension = 'com_content' AND c.published = 1 AND c.access IN (" . $in . ')'
            . ' AND NOT EXISTS (SELECT 1 FROM ' . $cat . " p WHERE p.extension = 'com_content' AND p.lft < c.lft AND p.rgt > c.rgt AND (p.published <> 1 OR p.access NOT IN (" . $in . '))))';
        if ($ex = $this->settings->excludeCats()) {
            $where .= ' AND ' . $db->quoteName('catid') . ' NOT IN (' . implode(',', $ex) . ')';
        }
        if ($ex = $this->settings->excludeIds()) {
            $where .= ' AND ' . $db->quoteName('id') . ' NOT IN (' . implode(',', $ex) . ')';
        }
        try {   // a multilingual site: only articles in this language (or in all languages)
            if (\Joomla\CMS\Language\Multilanguage::isEnabled()) {
                $where .= ' AND ' . $db->quoteName('lang') . ' IN (' . $db->quote('*') . ', ' . $db->quote(\Joomla\CMS\Factory::getApplication()->getLanguage()->getTag()) . ')';
            }
        } catch (\Throwable $e) {
        }
        return $where;
    }

    /** @param int[] $ids @return int[] the ids the visitor may see, in the same order */
    private function onlyVisible(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        try {
            $db = $this->db;
            $ok = array_flip(array_map('intval', $db->setQuery('SELECT id FROM ' . $db->quoteName('#__aisearch_docs')
                . ' WHERE id IN (' . implode(',', array_map('intval', $ids)) . ') AND ' . $this->visibleWhere())->loadColumn() ?: []));
        } catch (\Throwable $e) {
            return $ids;   // an index from before the upgrade has no date columns yet: show everything until it is rebuilt
        }
        return array_values(array_filter($ids, static fn ($id) => isset($ok[(int) $id])));
    }

    /**
     * Fast suggestions for the search module: titles that contain the typed text first, then the best keyword matches.
     * Uses only the database and the keyword index (never the AI), so it is cheap to call on every keystroke.
     * @return array<int,object> rows of #__aisearch_docs
     */
    public function suggest(string $q, int $n = 6): array
    {
        $q = trim(preg_replace('/\s+/u', ' ', $q));
        if (mb_strlen($q) < 2) {
            return [];
        }
        $db = $this->db;
        $esc = $db->escape(mb_substr($q, 0, 100), true);
        $rows = $db->setQuery($db->getQuery(true)->select('id, catid, title, category')->from($db->quoteName('#__aisearch_docs'))
            ->where($db->quoteName('title') . " LIKE '%" . $esc . "%'")
            ->where($this->visibleWhere())
            ->order("(" . $db->quoteName('title') . " LIKE '" . $esc . "%') DESC, CHAR_LENGTH(" . $db->quoteName('title') . ') ASC'), 0, $n)->loadObjectList('id');
        if (count($rows) < $n) {
            $r = $this->search($q, $n, 0, 20, false);
            foreach ($r['items'] as $d) {
                if (!isset($rows[$d->id]) && count($rows) < $n) {
                    $rows[$d->id] = $d;
                }
            }
        }
        return array_values($rows);
    }

    /** @return array{items:array,total:int,ms:float,indexed:bool,mode:string} mode = hybrid | keyword */
    public function search(string $q, int $limit = 20, int $offset = 0, int $max = 100, bool $semantic = true, ?bool $precision = null, bool $explain = false): array
    {
        $t0 = microtime(true);
        $bm = (new IndexStore($this->db))->load();
        if (!$bm) {
            return ['items' => [], 'total' => 0, 'ms' => 0.0, 'indexed' => false, 'mode' => 'keyword'];
        }
        $tk = new ThaiTokenizer();
        $qn = $tk->normalize($q);
        $db = $this->db;
        $known = static function (string $w) use ($db): bool {   // does any article contain this string?
            try {
                $e = $db->escape($w, true);
                return (int) $db->setQuery('SELECT COUNT(*) FROM (SELECT d.id FROM ' . $db->quoteName('#__aisearch_docs') . ' d LEFT JOIN ' . $db->quoteName('#__aisearch_text') . " t ON t.id = d.id WHERE d.title LIKE '%" . $e . "%' OR t.body LIKE '%" . $e . "%' LIMIT 3) x")->loadResult() >= 3;
            } catch (\Throwable $e2) {
                return false;
            }
        };
        $qt = (new QueryTerms($tk, $bm, $known))->analyse($q);
        $tokens = $qt['tokens'];   // the words that are left once question wording is dropped
        $units = $qt['units'];     // the same with compounds joined (เบา + หวาน -> เบาหวาน)
        $expanded = [];
        $fired = [];   // only the rules that apply to this search
        foreach ($this->synonyms() as $key => $alts) {
            if (SynonymStore::fires((string) $key, $qn, $tk)) {
                $fired[$key] = $alts;
                array_push($expanded, ...$alts);
            }
        }
        // a rule written "A + B = alternatives" says what a wish means: the first part is the topic, the other parts are the
        // wording of the wish ("นมแม่ + เยอะ" = "เพิ่มน้ำนม"). The wording is then replaced by the alternatives, so an article is not
        // ranked high just because it happens to contain the words "เยอะ" or "อยาก" (a title about sweet milk with "เยอะๆ").
        $concepts = [];
        $terms = $qt['terms'];
        foreach ($fired as $key => $alts) {
            if (!str_contains((string) $key, '+')) {
                continue;
            }
            $parts = array_values(array_filter(array_map(static fn ($p) => trim($p), explode('+', (string) $key)), static fn ($p) => $p !== ''));
            foreach (array_slice($parts, 1) as $mod) {
                $mn = $tk->normalize($mod);
                $units = array_values(array_filter($units, static fn ($u) => $u !== $mn));
                foreach ($tk->tokenize($mod) as $mt) {
                    $at = array_search($mt, $terms, true);
                    if ($at !== false) { unset($terms[$at]); }
                }
            }
            $concepts[] = $alts;
        }
        $terms = array_values($terms);
        $rankings = [[]]; $weights = []; $mode = 'keyword'; $vec = []; $rewrite = ''; $fused = []; $info = []; $hidden = 0;
        // a very short search (one word): every article with the word in its title, newest first, and nothing else
        $short = $this->titleMatches($units, $concepts, $fired, $tk, $bm);
        if ($short !== null) {
            $fused = $short['ids'];
            $info = $short['info'];
        } else {
            // precision mode starts from a much deeper list, because the right articles can sit far below the first hundred
            $usePrecision = $precision ?? $this->settings->precisionMode();
            $depth = $usePrecision ? 800 : 100;
            $rankings = [$bm->search($terms ?: $qt['terms'], $depth)];
            $weights = [1.0];
            if ($expanded) {
                $expTerms = [];
                foreach ($expanded as $alt) {   // each synonym is a phrase of its own: its words and its neighbouring pairs
                    $expTerms = array_merge($expTerms, ThaiTokenizer::withPairs($tk->tokenize($alt)));
                }
                $rankings[] = $bm->search($expTerms, $depth);
                $weights[] = 0.5;
            }
            $mode = 'keyword';
            $vec = [];
            $rewrite = '';
            if ($semantic && $this->settings->clickLearning()) {
                $learned = (new Clicks($this->db, $tk))->learned($q, $this->settings->clickMin());
                if ($learned) {
                    $rankings[] = $learned;
                    $weights[] = 1.0;
                }
            }
            $fused = $this->onlyVisible(array_keys(Rrf::fuse($rankings, 60, $weights)));
            $info = [];
            $hidden = 0;
            if ($usePrecision) {
                $pr = (new Precision($this->db, $tk, $bm, $fired, $this->settings->precisionCommonPct(), $this->settings->precisionMaxTier(), $this->settings->precisionKeepSemantic()))
                    ->apply($units, $fused, array_keys($vec), $qt['df'], $concepts);
                $fused = $pr['ids'];
                $info = $pr['info'];
                $hidden = $pr['hidden'];
            }
        }
        $all = array_slice($fused, 0, $max);
        $total = count($all);
        $ids = array_slice($all, max(0, $offset), $limit);
        $items = [];
        if ($ids) {
            $db = $this->db;
            $rows = $db->setQuery($db->getQuery(true)->select('*')->from($db->quoteName('#__aisearch_docs'))
                ->where($db->quoteName('id') . ' IN (' . implode(',', array_map('intval', $ids)) . ')'))->loadObjectList('id');
            foreach ($ids as $id) {
                if (isset($rows[$id])) {
                    $items[] = $rows[$id];
                }
            }
        }
        $res = ['items' => $items, 'total' => $total, 'ms' => round((microtime(true) - $t0) * 1000, 1), 'indexed' => true, 'mode' => $mode, 'hidden' => $hidden, 'rewrite' => $rewrite];
        if ($explain) {
            $res['explain'] = $info;
            $res['ranks'] = ['bm25' => array_flip(array_keys($rankings[0])), 'ai' => array_flip(array_keys($vec))];
        }
        return $res;
    }

    /** Writes one line to the search log and returns its id (the results page sends it back with a click). Old lines are dropped after the keep-days setting. */
    public function log(string $q, int $results, float $ms, string $mode = 'keyword'): int
    {
        $row = (object) ['q' => mb_substr($q, 0, 200), 'results' => $results, 'ms' => $ms, 'mode' => $mode, 'tracked' => 1, 'created' => gmdate('Y-m-d H:i:s')];
        try {
            $this->db->insertObject('#__aisearch_log', $row);
        } catch (\Throwable $e) {   // an upgrade that has not added the new columns yet
            unset($row->tracked);
            $this->db->insertObject('#__aisearch_log', $row);
            return 0;
        }
        $id = (int) $this->db->insertid();
        if (random_int(1, 100) === 1) {
            $db = $this->db;
            $db->setQuery('DELETE FROM ' . $db->quoteName('#__aisearch_log') . ' WHERE created < ' . $db->quote(gmdate('Y-m-d H:i:s', time() - $this->settings->logDays() * 86400)))->execute();
        }
        return $id;
    }
}
