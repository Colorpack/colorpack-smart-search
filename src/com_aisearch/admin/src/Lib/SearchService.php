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
        $qt = (new QueryTerms($tk, $bm))->analyse($q);
        $tokens = $qt['tokens'];   // the words that are left once question wording is dropped
        $units = $qt['units'];     // the same with compounds joined (เบา + หวาน -> เบาหวาน)
        $expanded = [];
        foreach ($this->synonyms() as $key => $alts) {
            if (mb_strpos($qn, $tk->normalize($key)) !== false) {
                array_push($expanded, ...$alts);
            }
        }
        // precision mode starts from a much deeper list, because the right articles can sit far below the first hundred
        $usePrecision = $precision ?? $this->settings->precisionMode();
        $depth = $usePrecision ? 800 : 100;
        $rankings = [$bm->search($qt['terms'], $depth)];
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
            $pr = (new Precision($this->db, $tk, $bm, $this->synonyms(), $this->settings->precisionCommonPct(), $this->settings->precisionMaxTier(), $this->settings->precisionKeepSemantic()))
                ->apply($units, $fused, array_keys($vec), $qt['df']);
            $fused = $pr['ids'];
            $info = $pr['info'];
            $hidden = $pr['hidden'];
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
