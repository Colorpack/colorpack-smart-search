<?php
declare(strict_types=1);
namespace Colorpack\Component\AiSearch\Administrator\Lib;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Precision mode: decides, for each candidate article, how directly it answers the search, and drops the ones that only
 * mention a word in passing. This is a ranking cascade (like Meilisearch / Algolia), not one blended score: a higher
 * tier always comes before a lower one, and inside a tier the existing order (BM25 + synonyms + AI) is kept.
 *
 *  Query with a number and a unit ("1 เดือน", "3 ขวบ"): the number and unit must stand together in the text.
 *    tier 1  the phrase is in the title
 *    tier 2  the phrase is in the tags or category
 *    tier 3  the phrase is in the body and the article is about the topic of the other words
 *    tier 4  the phrase is only somewhere in the body
 *  Other queries: the topic words (words that are not in most articles) must all be present.
 *    tier 1  all of them in the title          tier 2  all of them in title, tags and category
 *    tier 3  all of them in the text, and mentioned more than once or near the start
 *    tier 4  all of them somewhere / most of them
 *  Articles outside the shown tiers are hidden. Very common words ("เด็ก" is in most articles) only break ties.
 */
final class Precision
{
    public const UNITS = ['เดือน', 'ขวบ', 'ปี', 'สัปดาห์', 'อาทิตย์', 'วัน', 'month', 'months', 'year', 'years', 'week', 'weeks', 'day', 'days'];

    /** @param array<string,string[]> $synonyms phrase => alternatives */
    /** @var array<string,int> how many articles keep a joined unit together (for units made of several words) */
    private array $unitDf = [];
    /** @var array<int,string[]> a wish that a rule turned into alternatives: an article must have any one of them */
    private array $concepts = [];
    /** the article being judged (short words are looked up as words of that article, not as pieces of longer words) */
    private int $curId = 0;
    /** @var array<string,string[]> */
    private array $segCache = [];

    public function __construct(
        private DatabaseInterface $db,
        private ThaiTokenizer $tk,
        private Bm25 $bm,
        private array $synonyms,
        private int $commonPct = 40,
        private int $maxTier = 3,
        private int $keepSemantic = 0,
    ) {}

    /**
     * @param string[] $tokens    query words (already without filler words)
     * @param int[]    $ids       candidates in the order of the normal ranking
     * @param int[]    $semantic  ids ranked best by the AI similarity (may be kept as "related")
     * @return array{ids:int[],info:array<int,array<string,mixed>>,hidden:int,plan:array<string,mixed>}
     */
    public function apply(array $tokens, array $ids, array $semantic = [], array $unitDf = [], array $concepts = []): array
    {
        $this->unitDf = $unitDf;
        $this->concepts = $concepts;
        $plan = $this->plan($tokens);
        if (!$ids || (!$plan['phrases'] && !$plan['core'])) {
            return ['ids' => $ids, 'info' => [], 'hidden' => 0, 'plan' => $plan];
        }
        $texts = $this->texts($ids);
        $keepSem = array_flip(array_slice($semantic, 0, max(0, $this->keepSemantic)));
        $rows = [];
        foreach ($ids as $pos => $id) {
            $this->curId = (int) $id;
            $t = $texts[$id] ?? null;
            if ($t === null) {
                $rows[] = [$id, 9, 0, $pos, ['tier' => 9, 'why' => 'no text']];
                continue;
            }
            [$tier, $why, $ctx] = $plan['phrases'] ? $this->tierPhrase($plan, $t) : $this->tierWords($plan, $t);
            if ($tier > $this->maxTier && isset($keepSem[$id])) {
                $tier = $this->maxTier + 1;
                $why = 'ai-similar';
            }
            // among articles that only discuss the words in the text, the one that says them most comes first
            $dens = 0;
            if ($tier >= 3 && $tier < 9) {
                foreach ($plan['core'] as $w) {
                    $best = 0;
                    foreach ($this->formsOf($w) as $f) {
                        $best = max($best, mb_substr_count($t['title'] . ' ' . $t['body'], $f));
                    }
                    $dens += min(5, $best);
                }
            }
            $cov = $tier < 9 ? $this->coverage($plan, $t) : 0;
            $cls = $tier <= 2 && !$plan['phrases'] ? $this->titleCloseness($plan['core'], $t['title']) : 0;
            $rows[] = [$id, $tier, $ctx ? 1 : 0, $pos, ['tier' => $tier, 'why' => $why, 'context' => $ctx, 'fields' => $cov, 'close' => $cls], $dens, $cov, $cls];
        }
        // the better level first (the words in the title, then in tags / keywords / description, then in the text); inside a level the title that says the words close together comes first, then the article that has
        // the search in the most fields (title, tags, keywords, description, opening) comes first, then the rest
        usort($rows, static fn ($a, $b) => [$a[1], $a[7] ?? 0, -($a[6] ?? 0), -$a[2], -($a[5] ?? 0), $a[3]] <=> [$b[1], $b[7] ?? 0, -($b[6] ?? 0), -$b[2], -($b[5] ?? 0), $b[3]]);
        $out = []; $info = []; $hidden = 0;
        foreach ($rows as [$id, $tier, , , $inf]) {
            if ($tier <= $this->maxTier + ($this->keepSemantic > 0 ? 1 : 0) && $tier < 9) {
                $out[] = $id;
                $info[$id] = $inf;
            } else {
                $hidden++;
            }
        }
        return ['ids' => $out, 'info' => $info, 'hidden' => $hidden, 'plan' => $plan];
    }

    /** Words in the question as typed, a number with its unit counting as one, the repeat mark ๆ not at all. */
    private function wordCount(array $tokens): int
    {
        $n = 0;
        for ($i = 0, $c = count($tokens); $i < $c; $i++) {
            if ($tokens[$i] === 'ๆ') { continue; }
            if (ctype_digit($tokens[$i]) && isset($tokens[$i + 1]) && in_array(mb_strtolower($tokens[$i + 1]), self::UNITS, true)) { $i++; }
            $n++;
        }
        return $n;
    }

    /** Wording that says nothing about the topic of a question. */
    private const FILLER = ['อยาก', 'มี', 'เยอะ', 'มาก', 'หน่อย', 'ได้', 'ที่', 'คือ', 'เป็น', 'แบบ', 'ทำ', 'วิธี', 'อยู่', 'จะ', 'ถึง'];

    /** Splits the query into number+unit phrases, topic words ("core") and very common words ("context"). */
    private function plan(array $tokens): array
    {
        $phrases = []; $rest = [];
        for ($i = 0, $n = count($tokens); $i < $n; $i++) {
            $t = $tokens[$i];
            if (ctype_digit($t) && isset($tokens[$i + 1]) && in_array(mb_strtolower($tokens[$i + 1]), self::UNITS, true)) {
                $phrases[] = ['n' => $t, 'u' => $tokens[$i + 1]];
                $i++;
                continue;
            }
            $rest[] = $t;
        }
        $N = max(1, $this->bm->docCount()); $core = []; $ctx = [];
        foreach (array_values(array_unique($rest)) as $t) {
            $common = $N >= 50 && !ctype_digit($t) && (100 * ($this->unitDf[$t] ?? $this->bm->docFreq($t)) / $N) >= $this->commonPct;
            if ($common) {
                $ctx[] = $t;
            } else {
                $core[] = $t;
            }
        }
        if (!$core && !$phrases) {
            $core = $ctx;   // every word is common: use them all rather than nothing
            $ctx = [];
        }
        $core = array_values(array_filter($core, static fn ($w) => !in_array($w, self::FILLER, true) && $w !== 'ๆ'));
        foreach ($this->concepts as $i => $alts) {
            $core[] = '§' . $i;   // stands for the alternatives of a rule such as "นมแม่ + เยอะ"
        }
        return ['phrases' => $phrases, 'core' => $core, 'context' => $ctx];
    }

    /** @return array<int,array{title:string,meta:string,body:string}> normalised texts by article id */
    private function texts(array $ids): array
    {
        $db = $this->db; $out = [];
        try {
            $rows = $db->setQuery('SELECT d.id, d.title, d.category, d.tags, t.body, t.kw FROM ' . $db->quoteName('#__aisearch_docs', 'd')
                . ' LEFT JOIN ' . $db->quoteName('#__aisearch_text', 't') . ' ON t.id = d.id WHERE d.id IN (' . implode(',', array_map('intval', $ids)) . ')')->loadObjectList();
        } catch (\Throwable $e) {
            return [];
        }
        foreach ($rows as $r) {
            [$keys, $desc] = array_pad(explode("\u{00A6}", (string) ($r->kw ?? ''), 2), 2, '');
            $body = (string) ($r->body ?? '');
            $out[(int) $r->id] = [
                'title' => $this->tk->normalize((string) $r->title),
                'meta' => $this->tk->normalize((string) $r->tags . ' ' . (string) $r->category) . ' ' . $keys . ' ' . $desc,
                'body' => $body,
                // the fields a visitor expects a search word to appear in, kept apart so they can be counted
                'tags' => $this->tk->normalize((string) $r->tags),
                'keys' => $keys,
                'desc' => $desc,
                'intro' => mb_substr($body, 0, 300),
            ];
        }
        return $out;
    }

    private function phraseRe(array $p): string
    {
        return '/(?<![\d\-–.])' . preg_quote($p['n'], '/') . '\s*' . preg_quote($this->tk->normalize($p['u']), '/') . '/u';
    }

    /** @var array<string,string[]> */
    private array $forms = [];

    /** The word and its synonyms, normalised (worked out once per word). */
    private function formsOf(string $word): array
    {
        if (!isset($this->forms[$word])) {
            if (str_starts_with($word, '§')) {
                return $this->forms[$word] = array_values(array_filter(array_map(fn ($a) => $this->tk->normalize((string) $a), $this->concepts[(int) substr($word, 1)] ?? []), static fn ($x) => $x !== ''));
            }
            $f = [$word];
            foreach ($this->synonyms as $key => $alts) {
                if (mb_strpos($this->tk->normalize($key), $word) !== false) {
                    foreach ($alts as $a) {
                        $f[] = $this->tk->normalize($a);
                    }
                }
            }
            $this->forms[$word] = array_values(array_filter(array_unique($f), static fn ($x) => $x !== ''));
        }
        return $this->forms[$word];
    }

    /** Is the word (or one of its synonyms) in the text? Numbers must not be part of a longer number. */
    private function has(string $text, string $word): bool
    {
        if ($text === '') {
            return false;
        }
        foreach ($this->formsOf($word) as $f) {
            if (ctype_digit($f)) {
                if (preg_match('/(?<![\d])' . $f . '(?![\d])/u', $text) === 1) {
                    return true;
                }
            } elseif ($this->isShort($f)) {
                if ($this->hasShort($text, $f)) {
                    return true;
                }
            } elseif (mb_strpos($text, $f) !== false) {
                return true;
            }
        }
        return false;
    }

    /** A Thai word of up to three letters (ยา, นม, ไม่, ลูก) is often a piece of a longer one (น้ำยา, นมัสการ, ลูกชิ้น). */
    private function isShort(string $w): bool
    {
        return mb_strlen($w) <= 3 && preg_match('/^\p{Thai}+$/u', $w) === 1;
    }

    /**
     * Is the short word a word of its own in this text? A long text (the body) is checked against the index, which holds the words ICU found;
     * a short field (title, tags, description) is cut into words here.
     */
    private function hasShort(string $text, string $w): bool
    {
        if (mb_strlen($text) > 700 && $this->curId > 0) {
            return isset($this->bm->docsOf($w)[$this->curId]);
        }
        $k = md5($text);
        $this->segCache[$k] ??= $this->tk->tokenize($text);
        return in_array($w, $this->segCache[$k], true);
    }

    private function allIn(array $words, string $text): bool
    {
        foreach ($words as $w) {
            if (!$this->has($text, $w)) {
                return false;
            }
        }
        return true;
    }

    /**
     * In how many of the five fields (title, tags, Meta Keywords, Meta Description, opening of the article) does each article have the search?
     * @param int[] $ids @return array<int,int> id => 0..5
     */
    public function coverageOf(array $tokens, array $ids): array
    {
        $plan = $this->plan($tokens);
        $out = [];
        foreach ($this->texts($ids) as $id => $t) {
            $out[$id] = $this->coverage($plan, $t);
        }
        return $out;
    }

    /**
     * How close together the topic words stand in the title, as a class: 0 = within a short phrase (the title is about them),
     * 1 = within a sentence, 2 = far apart, 3 = not all in the title. A title that says "ท้องปุ๊บ เป็นเบาหวาน" is about both;
     * one that has the first word at its start and the second at its very end only mentions the second.
     */
    private function titleCloseness(array $core, string $title): int
    {
        $words = array_values($core);   // a rule's alternatives (§) count as a word: the title is about them when they stand close to the others
        if (count($words) < 2) {
            return $words && $this->has($title, $words[0]) ? 0 : 3;
        }
        $pos = [];
        foreach ($words as $w) {
            $best = [];
            foreach ($this->formsOf($w) as $f) {
                $o = 0;
                while (count($best) < 20 && ($p = mb_strpos($title, $f, $o)) !== false) {
                    $best[] = [$p, mb_strlen($f)];
                    $o = $p + 1;
                }
            }
            if (!$best) {
                return 3;
            }
            $pos[] = $best;
        }
        $min = PHP_INT_MAX;
        foreach ($pos[0] as [$p0, $l0]) {
            $lo = $p0; $hi = $p0 + $l0;
            foreach (array_slice($pos, 1) as $list) {
                $near = null;
                foreach ($list as [$p, $l]) {
                    if ($near === null || abs($p - $p0) < abs($near[0] - $p0)) {
                        $near = [$p, $l];
                    }
                }
                $lo = min($lo, $near[0]);
                $hi = max($hi, $near[0] + $near[1]);
            }
            $min = min($min, $hi - $lo);
        }
        return $min <= 24 ? 0 : ($min <= 48 ? 1 : 2);
    }

    /** Does this piece of text have everything the search asks for (all its words, or its number and unit)? */
    private function covers(array $plan, string $text): bool
    {
        if ($text === '') {
            return false;
        }
        if ($plan['phrases']) {
            foreach ($plan['phrases'] as $p) {
                if (!preg_match($this->phraseRe($p), $text)) {
                    return false;
                }
            }
        } elseif (!$plan['core']) {
            return false;
        }
        return !$plan['core'] || $this->allIn($plan['core'], $text);
    }

    /** In how many of title, tags, Meta Keywords, Meta Description and the opening of the article does the search appear in full? */
    private function coverage(array $plan, array $t): int
    {
        $n = 0;
        foreach (['title', 'tags', 'keys', 'desc', 'intro'] as $f) {
            if ($this->covers($plan, (string) ($t[$f] ?? ''))) {
                $n++;
            }
        }
        return $n;
    }

    /** @return array{0:int,1:string,2:bool} tier, reason, has the context in title/tags/category */
    private function tierPhrase(array $plan, array $t): array
    {
        $re = array_map(fn ($p) => $this->phraseRe($p), $plan['phrases']);
        $all = static function (string $s) use ($re): bool { foreach ($re as $r) { if (!preg_match($r, $s)) { return false; } } return true; };
        $metaAll = $t['title'] . ' ' . $t['meta'];
        $ctx = !$plan['context'] || $this->allIn($plan['context'], $metaAll);
        $coreOk = !$plan['core'] || $this->allIn($plan['core'], $metaAll . ' ' . $t['body']);
        if ($all($t['title'])) {
            return [$coreOk ? 1 : 4, $coreOk ? 'phrase in title' : 'phrase in title, other words missing', $ctx];
        }
        if ($all($t['meta'])) {
            return [$coreOk ? 2 : 4, 'phrase in tags/category', $ctx];
        }
        if ($all($t['body'])) {
            $about = $coreOk && $ctx && $this->phraseDiscussed($re, $t['body']);
            return [$about ? 3 : 4, $about ? 'phrase discussed in the text' : 'phrase only mentioned in the text', $ctx];
        }
        return [9, 'phrase not found', $ctx];
    }

    /** @return array{0:int,1:string,2:bool} */
    private function tierWords(array $plan, array $t): array
    {
        $core = $plan['core'];
        $ctx = !$plan['context'] || $this->allIn($plan['context'], $t['title'] . ' ' . $t['meta']);
        if ($this->allIn($core, $t['title'])) {
            // tier 1 only when the words stand close together; words scattered across a long title are a weaker match
            if (count($core) < 2 || $this->tight($core, $t['title'])) {   // only the topic words count here, not background words like เด็ก / ลูก
                return [1, 'all topic words in title, close together', $ctx];
            }
            return [2, 'topic words in title, but far apart', $ctx];
        }
        if ($this->allIn($core, $t['title'] . ' ' . $t['meta'])) {
            return [2, 'all topic words in title/tags/category', $ctx];
        }
        if ($this->allIn($core, $t['title'] . ' ' . $t['meta'] . ' ' . $t['body'])) {
            return [$this->topical($core, $t['body']) ? 3 : 4, $this->topical($core, $t['body']) ? 'all topic words, discussed in the text' : 'topic words only mentioned', $ctx];
        }
        $found = 0;
        foreach ($core as $w) {
            if ($this->has($t['title'] . ' ' . $t['meta'] . ' ' . $t['body'], $w)) {
                $found++;
            }
        }
        if (count($core) >= 4 && $found >= (int) ceil(0.7 * count($core))) {   // a long question: most of its topic words are enough
            return [3, 'most topic words', $ctx];
        }
        if (count($core) >= 3 && $found >= (int) ceil(0.5 * count($core))) {
            return [4, 'some topic words', $ctx];
        }
        return [9, 'topic words missing', $ctx];
    }

    /**
     * Do the words stand within a short stretch of the text? The stretch may be a few characters longer than the words
     * themselves (a filler word such as ให้ in between is fine).
     */
    private function tight(array $words, string $text): bool
    {
        $sum = 0; $pos = [];
        foreach ($words as $w) {
            $best = null;
            foreach ($this->formsOf($w) as $f) {
                $o = 0; $found = [];
                while (count($found) < 20 && ($p = mb_strpos($text, $f, $o)) !== false) {
                    $found[] = [$p, mb_strlen($f)];
                    $o = $p + 1;
                }
                if ($found && ($best === null || count($found) > count($best))) {
                    $best = $found;
                }
            }
            if (!$best) {
                return false;
            }
            $pos[] = $best;
            $sum += $best[0][1];
        }
        $limit = $sum + 6;
        foreach ($pos[0] as [$p0, $l0]) {
            $lo = $p0; $hi = $p0 + $l0; $ok = true;
            foreach (array_slice($pos, 1) as $list) {
                $near = null;
                foreach ($list as [$p, $l]) {
                    if ($near === null || abs($p - $p0) < abs($near[0] - $p0)) {
                        $near = [$p, $l];
                    }
                }
                $lo = min($lo, $near[0]);
                $hi = max($hi, $near[0] + $near[1]);
            }
            if ($hi - $lo <= $limit) {
                return true;
            }
        }
        return false;
    }

    /** The phrase is discussed when it appears more than once, or in the opening of the text. */
    private function phraseDiscussed(array $patterns, string $body): bool
    {
        foreach ($patterns as $re) {
            if (!preg_match($re, $body, $m, PREG_OFFSET_CAPTURE)) {
                return false;
            }
            $firstChars = mb_strlen(substr($body, 0, (int) $m[0][1]));
            if ($firstChars >= 400 && preg_match_all($re, $body) < 2) {
                return false;
            }
        }
        return true;
    }

    /** A word is discussed (not just mentioned) when it appears more than once or in the opening of the text. */
    private function topical(array $words, string $body): bool
    {
        foreach ($words as $w) {
            $first = mb_strpos($body, $w);
            if ($first === false) {
                continue;
            }
            if ($first < 400 || mb_substr_count($body, $w) >= 2) {
                continue;
            }
            return false;
        }
        return true;
    }
}
