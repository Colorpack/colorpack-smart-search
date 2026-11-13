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
    private const WORDS = ['ทําไม', 'ทำไม', 'ถึง', 'ไหน', 'กว่า', 'กัน', 'เป็น', 'ตอน', 'จะ', 'ไง', 'ยัง', 'ทํา', 'ทำ', 'อะไร', 'อย่างไร', 'ไหม', 'นะ', 'ค่ะ', 'คะ', 'ครับ', 'ต้อง', 'ควร', 'ดี', 'เมื่อ', 'ให้', 'ขณะ', 'ระหว่าง', 'อยู่'];
    /** Words that are question wording only when followed by another word of the pattern: "แบบ" + "ไหน". */
    private const PAIRS = [['แบบ', 'ไหน'], ['ยี่ห้อ', 'ไหน'], ['อัน', 'ไหน'], ['วิธี', 'ไหน']];
    /** Words that stand next to many other words and are never the second half of a compound (ห้าม, ไม่, ได้, กิน ...). */
    private const NEVER_JOIN = ['ห้าม', 'ไม่', 'ได้', 'มี', 'เป็น', 'กับ', 'แก่', 'แด่', 'ของ', 'ใน', 'จาก', 'ถึง', 'แล้ว', 'จะ', 'ยัง', 'คือ', 'เพื่อ', 'ซึ่ง', 'หรือ', 'และ', 'ก็', 'ให้', 'ต้อง', 'ควร', 'อย่า', 'ช่วย', 'กิน', 'ใช้', 'ทาน', 'ดื่ม', 'รับประทาน'];
    private const KEEP_APART = ['เดือน', 'ขวบ', 'ปี', 'สัปดาห์', 'อาทิตย์', 'วัน', 'month', 'months', 'year', 'years', 'week', 'weeks', 'day', 'days'];

    /** @param ?\Closure $known function(string[]): array<string,bool>, which of these strings are words that the articles themselves contain? */
    /** @var array<string,string> words the visitor mistyped => the word that was searched instead */
    public array $corrected = [];

    /** Is this word only question or linking wording (ทำไม, ขณะ, ตอน)? */
    public static function isFiller(string $t): bool
    {
        return in_array($t, self::WORDS, true) || $t === 'ๆ';
    }

    public function __construct(private ThaiTokenizer $tk, private Bm25 $bm, private ?\Closure $known = null) {}

    /**
     * @return array{tokens:string[],units:string[],terms:string[],df:array<string,int>}
     *   tokens = every word kept, in order; units = the same with compounds joined; terms = what BM25 should look for; df = how common each joined unit is
     */
    public function analyse(string $q): array
    {
        $tokens = []; $units = []; $terms = []; $df = [];
        foreach (preg_split('/[\s\x{200B}]+/u', trim($q)) ?: [] as $chunk) {
            $raw = $this->fixTypos($this->tk->tokenize($chunk));
            // Words typed together are one word in Thai. If the articles contain this very string, it is a word, however ICU cuts it
            // (คนท้อง -> คน + ท้อง, ท้องเสีย -> ท้อง + เสีย): look for it as a whole instead of its pieces.
            $whole = $this->corrected ? implode('', $raw) : $this->tk->normalize($chunk);
            if ($this->known && count($raw) >= 2) {
                $this->mergeKnown($chunk, $whole, $raw, $tokens, $units, $terms, $df);
                continue;
            }
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
        return ['tokens' => $tokens, 'units' => $units, 'terms' => $terms, 'df' => $df, 'corrected' => $this->corrected];
    }

    /**
     * Cuts one typed chunk into the words the articles really use. ICU cuts คนท้อง into คน + ท้อง and ท้องเสียขณะตั้งครรภ์ into four pieces;
     * here the longest runs of neighbouring pieces whose joined text is found in the articles become one unit each (ท้องเสีย, ตั้งครรภ์),
     * and question wording (ขณะ, ตอน, ทำไม) is left out.
     * @param string[] $raw
     */
    private function mergeKnown(string $chunk, string $whole, array $raw, array &$tokens, array &$units, array &$terms, array &$df): void
    {
        $n = count($raw);
        $drop = $this->dropMask($raw);   // question wording, also a "แบบ" that is followed by "ไหน"
        // a piece that cannot be part of a longer word: dropped wording, a number, a unit of time
        $apart = static fn (int $i) => $drop[$i] || ctype_digit($raw[$i]) || in_array($raw[$i], self::KEEP_APART, true) || in_array($raw[$i], self::NEVER_JOIN, true);
        $cands = [];
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < min($n, $i + 3); $j++) {
                if ($apart($i) || $apart($j)) {
                    break;
                }
                $cands[implode('', array_slice($raw, $i, $j - $i + 1))] = true;
            }
        }
        $clean = true;
        for ($i = 0; $i < $n; $i++) { if ($apart($i)) { $clean = false; } }
        if ($clean && mb_strlen($whole) >= 4 && mb_strlen($whole) <= 24) {
            $cands[$whole] = true;
        }
        $known = $cands ? ($this->known)(array_keys($cands)) : [];
        if ($clean && !empty($known[$whole])) {   // the whole chunk is one word
            $this->emit($raw, $whole, $tokens, $units, $terms, $df);
            return;
        }
        $i = 0;
        while ($i < $n) {
            if ($drop[$i]) {
                $i++;
                continue;
            }
            if ($apart($i)) {   // numbers and units are kept as they are
                $tokens[] = $raw[$i]; $units[] = $raw[$i]; $terms[] = $raw[$i];
                $i++;
                continue;
            }
            $take = 1;
            for ($len = min(3, $n - $i); $len >= 2; $len--) {
                $ok = true;
                for ($k = $i; $k < $i + $len; $k++) { if ($apart($k)) { $ok = false; } }
                if ($ok && !empty($known[implode('', array_slice($raw, $i, $len))])) {
                    $take = $len;
                    break;
                }
            }
            $slice = array_slice($raw, $i, $take);
            $this->emit($slice, implode('', $slice), $tokens, $units, $terms, $df);
            $i += $take;
        }
    }

    /** @param string[] $parts */
    private function emit(array $parts, string $unit, array &$tokens, array &$units, array &$terms, array &$df): void
    {
        $dfv = PHP_INT_MAX;
        for ($i = 1, $c = count($parts); $i < $c; $i++) {
            $pair = $parts[$i - 1] . ThaiTokenizer::JOIN . $parts[$i];
            $terms[] = $pair;
            $dfv = min($dfv, $this->bm->docFreq($pair));
        }
        foreach ($parts as $t) { $tokens[] = $t; $terms[] = $t; }
        $units[] = $unit;
        if (count($parts) > 1) {
            $df[$unit] = $dfv;
        }
    }

    /**
     * A Thai word that no article contains, and that is one letter away from a word they do contain a lot (ครรค์ -> ครรภ์), is taken as a typing mistake.
     * @param string[] $tokens @return string[]
     */
    private function fixTypos(array $tokens): array
    {
        foreach ($tokens as $i => $t) {
            if (mb_strlen($t) >= 3 && !ctype_digit($t) && preg_match('/^\p{Thai}+$/u', $t) && $this->bm->docFreq($t) === 0 && ($fix = $this->bm->nearest($t, 20)) !== null) {
                $this->corrected[$t] = $fix;
                $tokens[$i] = $fix;
            }
        }
        return $tokens;
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
        if (ctype_digit($a) || ctype_digit($b) || in_array($a, self::KEEP_APART, true) || in_array($b, self::KEEP_APART, true) || in_array($a, self::NEVER_JOIN, true) || in_array($b, self::NEVER_JOIN, true)) {
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
