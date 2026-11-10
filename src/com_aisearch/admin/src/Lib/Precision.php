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
    public function apply(array $tokens, array $ids, array $semantic = []): array
    {
        $plan = $this->plan($tokens);
        if (!$ids || (!$plan['phrases'] && !$plan['core'])) {
            return ['ids' => $ids, 'info' => [], 'hidden' => 0, 'plan' => $plan];
        }
        $texts = $this->texts($ids);
        $keepSem = array_flip(array_slice($semantic, 0, max(0, $this->keepSemantic)));
        $rows = [];
        foreach ($ids as $pos => $id) {
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
            $rows[] = [$id, $tier, $ctx ? 1 : 0, $pos, ['tier' => $tier, 'why' => $why, 'context' => $ctx]];
        }
        usort($rows, static fn ($a, $b) => [$a[1], -$a[2], $a[3]] <=> [$b[1], -$b[2], $b[3]]);
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
            $common = $N >= 50 && !ctype_digit($t) && (100 * $this->bm->docFreq($t) / $N) >= $this->commonPct;
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
        return ['phrases' => $phrases, 'core' => $core, 'context' => $ctx];
    }

    /** @return array<int,array{title:string,meta:string,body:string}> normalised texts by article id */
    private function texts(array $ids): array
    {
        $db = $this->db; $out = [];
        try {
            $rows = $db->setQuery('SELECT d.id, d.title, d.category, d.tags, t.body FROM ' . $db->quoteName('#__aisearch_docs', 'd')
                . ' LEFT JOIN ' . $db->quoteName('#__aisearch_text', 't') . ' ON t.id = d.id WHERE d.id IN (' . implode(',', array_map('intval', $ids)) . ')')->loadObjectList();
        } catch (\Throwable $e) {
            return [];
        }
        foreach ($rows as $r) {
            $out[(int) $r->id] = [
                'title' => $this->tk->normalize((string) $r->title),
                'meta' => $this->tk->normalize((string) $r->tags . ' ' . (string) $r->category),
                'body' => (string) ($r->body ?? ''),
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
            if (ctype_digit($f) ? preg_match('/(?<![\d])' . $f . '(?![\d])/u', $text) === 1 : mb_strpos($text, $f) !== false) {
                return true;
            }
        }
        return false;
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
        if (count($core) >= 3 && $found >= (int) ceil(0.6 * count($core))) {
            return [4, 'most topic words', $ctx];
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
