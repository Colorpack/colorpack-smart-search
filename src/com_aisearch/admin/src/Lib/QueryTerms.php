<?php
declare(strict_types=1);
namespace Colorpack\Component\AiSearch\Administrator\Lib;

\defined('_JEXEC') or die;

/**
 * Turns what a visitor typed into what the search should look for:
 *  - question wording is dropped ("ทำไมถึง", "แบบไหน", "ยี่ห้อไหน", "ทำยังไง"), because no article is about "ทำไมถึง";
 *  - words typed together that the articles also keep together are joined into one unit (เบา + หวาน -> เบาหวาน),
 *    judged from the index itself, so no word list has to be kept by hand;
 *  - numbers and their units (1 เดือน) are left as they are.
 */
final class QueryTerms
{
    /** Words that only make a sentence a question. */
    private const WORDS = ['ทําไม', 'ทำไม', 'ถึง', 'ไหน', 'กว่า', 'กัน', 'เป็น', 'ตอน', 'จะ', 'ไง', 'ยัง', 'ทํา', 'ทำ', 'อะไร', 'อย่างไร', 'ไหม', 'นะ', 'ค่ะ', 'คะ', 'ครับ', 'ต้อง', 'ควร', 'ดี', 'เมื่อ', 'ให้'];
    /** Words that are question wording only when followed by another word of the pattern: "แบบ" + "ไหน". */
    private const PAIRS = [['แบบ', 'ไหน'], ['ยี่ห้อ', 'ไหน'], ['อัน', 'ไหน'], ['วิธี', 'ไหน']];
    private const KEEP_APART = ['เดือน', 'ขวบ', 'ปี', 'สัปดาห์', 'อาทิตย์', 'วัน', 'month', 'months', 'year', 'years', 'week', 'weeks', 'day', 'days'];

    public function __construct(private ThaiTokenizer $tk, private Bm25 $bm) {}

    /**
     * @return array{tokens:string[],units:string[],terms:string[],df:array<string,int>}
     *   tokens = every word kept, in order; units = the same with compounds joined; terms = what BM25 should look for; df = how common each joined unit is
     */
    public function analyse(string $q): array
    {
        $tokens = []; $units = []; $terms = []; $df = [];
        foreach (preg_split('/[\s\x{200B}]+/u', trim($q)) ?: [] as $chunk) {
            $raw = $this->tk->tokenize($chunk);
            $drop = $this->dropMask($raw);
            $run = [];
            foreach ($raw as $i => $t) {
                if ($drop[$i]) {
                    $this->flush($run, $tokens, $units, $terms, $df);
                    continue;
                }
                if ($run && !$this->joins(end($run), $t)) {
                    $this->flush($run, $tokens, $units, $terms, $df);
                }
                $run[] = $t;
            }
            $this->flush($run, $tokens, $units, $terms, $df);
        }
        return ['tokens' => $tokens, 'units' => $units, 'terms' => $terms, 'df' => $df];
    }

    /** @param string[] $raw @return bool[] */
    private function dropMask(array $raw): array
    {
        $drop = array_fill(0, count($raw), false);
        foreach ($raw as $i => $t) {
            if (in_array($t, self::WORDS, true) || $t === 'ๆ') {
                $drop[$i] = true;
            }
            foreach (self::PAIRS as [$a, $b]) {
                if ($t === $a && ($raw[$i + 1] ?? '') === $b) {
                    $drop[$i] = true;
                }
            }
        }
        return $drop;
    }

    /** Do these two neighbours stand together in the articles more often than not? */
    private function joins(string $a, string $b): bool
    {
        if (ctype_digit($a) || ctype_digit($b) || in_array($a, self::KEEP_APART, true) || in_array($b, self::KEEP_APART, true)) {
            return false;
        }
        $pair = $this->bm->docFreq($a . ThaiTokenizer::JOIN . $b);
        return $pair >= 5 && $pair >= 0.35 * min($this->bm->docFreq($a), $this->bm->docFreq($b));
    }

    /** @param string[] $run */
    private function flush(array &$run, array &$tokens, array &$units, array &$terms, array &$df): void
    {
        if (!$run) {
            return;
        }
        // the run is a chain of neighbours that join: one unit, found through its pair terms
        $joined = [$run[0]];
        $buf = [$run[0]];
        $parts = [];
        for ($i = 1, $n = count($run); $i < $n; $i++) {
            if ($this->joins($run[$i - 1], $run[$i])) {
                $buf[] = $run[$i];
            } else {
                $parts[] = $buf; $buf = [$run[$i]];
            }
        }
        $parts[] = $buf;
        foreach ($parts as $p) {
            $unit = implode('', $p);
            $units[] = $unit;
            foreach ($p as $t) { $tokens[] = $t; $terms[] = $t; }
            if (count($p) > 1) {
                $dfv = PHP_INT_MAX;
                for ($i = 1, $n = count($p); $i < $n; $i++) {
                    $pair = $p[$i - 1] . ThaiTokenizer::JOIN . $p[$i];
                    $terms[] = $pair;
                    $dfv = min($dfv, $this->bm->docFreq($pair));
                }
                $df[$unit] = $dfv;
            }
        }
        $run = [];
    }
}
