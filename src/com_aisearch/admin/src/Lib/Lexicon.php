<?php
declare(strict_types=1);
namespace Colorpack\Component\AiSearch\Administrator\Lib;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * The words of the site. ICU cuts some Thai words in two (เบาหวาน -> เบา + หวาน, น้ำยา -> น้ำ + ยา); a lexicon puts the pieces back together,
 * in the index and in the search alike. It has three layers: a general word list (a file next to this code), the words of the site itself
 * (tag and category names, found when the index is rebuilt) and words the editors add on the Words page.
 * The search uses the snapshot saved with the index, so editing the words changes nothing until the index is rebuilt.
 */
final class Lexicon
{
    /** the lexicon the tokenizer uses right now (null = ICU alone, as before) */
    public static ?Lexicon $active = null;
    private static ?string $bootedFor = null;
    public const MAX_PARTS = 5;

    /** @param array<string,true> $words @param array<string,true> $split */
    public function __construct(private array $words, private array $split = []) {}

    public function count(): int
    {
        return count($this->words);
    }

    public function has(string $w): bool
    {
        return isset($this->words[$w]) && !isset($this->split[$w]);
    }

    /**
     * Joins neighbouring pieces whose text together is a word of the lexicon, the longest run first.
     * @param string[] $pieces @param bool[] $breaks true = this piece may not be joined with its neighbours (a stop word, a number ...)
     * @return string[]
     */
    public function merge(array $pieces, array $breaks = []): array
    {
        $n = count($pieces);
        if ($n < 2) {
            return $pieces;
        }
        $out = [];
        $i = 0;
        while ($i < $n) {
            $took = 1;
            if (empty($breaks[$i])) {
                for ($len = min(self::MAX_PARTS, $n - $i); $len >= 2; $len--) {
                    $ok = true;
                    for ($k = $i; $k < $i + $len; $k++) {
                        if (!empty($breaks[$k])) { $ok = false; break; }
                    }
                    if ($ok && $this->has(implode('', array_slice($pieces, $i, $len)))) {
                        $took = $len;
                        break;
                    }
                }
            }
            $out[] = $took === 1 ? $pieces[$i] : implode('', array_slice($pieces, $i, $took));
            $i += $took;
        }
        return $out;
    }

    // ---- sources ------------------------------------------------------------------------------------------------

    private static function norm(string $w): string
    {
        return (new ThaiTokenizer())->normalize($w);
    }

    /** @return string[] words of a text file, one per line, # for comments */
    private static function fileWords(string $file): array
    {
        $out = [];
        foreach (@file($file, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '' && $line[0] !== '#') {
                $out[] = $line;
            }
        }
        return $out;
    }

    /** All the words as they are now: files, the words table (editors and the site's own tag and category names). @return array{words:array<string,true>,split:array<string,true>,counts:array<string,int>} */
    public static function collect(DatabaseInterface $db): array
    {
        $words = []; $split = []; $counts = ['base' => 0, 'starter' => 0, 'site' => 0, 'manual' => 0, 'split' => 0];
        $tk = new ThaiTokenizer();
        foreach (['base' => __DIR__ . '/../Lexicon/th_base.txt', 'starter' => __DIR__ . '/../Lexicon/th_domain.txt'] as $kind => $file) {
            foreach (self::fileWords($file) as $w) {
                $n = $tk->normalize($w);
                if ($n !== '' && mb_strlen($n) >= 2) { $words[$n] = true; $counts[$kind]++; }
            }
        }
        try {
            foreach ($db->setQuery('SELECT word, kind, source FROM ' . $db->quoteName('#__aisearch_words') . ' WHERE enabled = 1')->loadObjectList() ?: [] as $r) {
                $n = $tk->normalize((string) $r->word);
                if ($n === '') { continue; }
                if ($r->kind === 'split') { $split[$n] = true; $counts['split']++; }
                else { $words[$n] = true; $counts[in_array($r->source, ['tag', 'cat'], true) ? 'site' : 'manual']++; }
            }
        } catch (\Throwable $e) {
        }
        return ['words' => $words, 'split' => $split, 'counts' => $counts];
    }

    /**
     * Adds the names of the site's tags and categories to the words table (once each; an editor's later choice is never overwritten).
     * A name that ICU already keeps whole, or that has a space in it, adds nothing and is skipped.
     * @return int words added
     */
    public static function mineSite(DatabaseInterface $db): int
    {
        $tk = new ThaiTokenizer();
        $names = [];
        try {
            foreach ($db->setQuery("SELECT title FROM " . $db->quoteName('#__tags') . " WHERE published = 1 AND level > 0")->loadColumn() ?: [] as $t) { $names[(string) $t] = 'tag'; }
            foreach ($db->setQuery("SELECT title FROM " . $db->quoteName('#__categories') . " WHERE published = 1 AND extension = 'com_content'")->loadColumn() ?: [] as $t) { $names[(string) $t] = 'cat'; }
            // the names found last time are worked out again (an editor's choice to switch one off stays); the rows an editor typed are never touched
            $db->setQuery('DELETE FROM ' . $db->quoteName('#__aisearch_words') . " WHERE source IN ('tag', 'cat') AND enabled = 1")->execute();
            $have = array_flip(array_map('strval', $db->setQuery('SELECT word FROM ' . $db->quoteName('#__aisearch_words'))->loadColumn() ?: []));
        } catch (\Throwable $e) {
            return 0;
        }
        $now = $db->quote(gmdate('Y-m-d H:i:s'));
        $rows = [];
        foreach ($names as $name => $src) {
            $w = $tk->normalize($name);
            if (isset($have[$w]) || !preg_match('/^\p{Thai}+$/u', $w) || mb_strlen($w) < 3 || mb_strlen($w) > 16 || count($tk->tokenize($w)) !== 2) {
                continue;   // not a Thai compound of two words (a longer name is a phrase of several words: it stays several words), or already whole
            }
            $have[$w] = true;
            $rows[] = '(' . $db->quote($w) . ", 'keep', 1, " . $db->quote($src) . ', ' . $now . ')';
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            $db->setQuery('INSERT IGNORE INTO ' . $db->quoteName('#__aisearch_words') . ' (word, kind, enabled, source, created) VALUES ' . implode(',', $chunk))->execute();
        }
        return count($rows);
    }

    /**
     * Words the site uses that are not in the lexicon yet: pairs and triples of neighbouring ICU pieces that stand together far more often than chance
     * (normalised pointwise mutual information) and often enough. The editor decides; nothing is added by itself.
     * @return array<int,array{word:string,count:int,score:float}>
     */
    public static function candidates(DatabaseInterface $db, int $limit = 60, int $minCount = 8): array
    {
        $tk = new ThaiTokenizer();
        $keep = self::$active;
        self::$active = null;   // the pieces as ICU cuts them
        $have = self::collect($db);
        $uni = []; $bi = []; $total = 0;
        try {
            $rows = $db->setQuery('SELECT d.title, t.body FROM ' . $db->quoteName('#__aisearch_docs') . ' d LEFT JOIN ' . $db->quoteName('#__aisearch_text') . ' t ON t.id = d.id')->loadObjectList() ?: [];
            foreach ($rows as $r) {
                $toks = $tk->tokenize((string) $r->title . ' ' . mb_substr((string) $r->body, 0, 2500));
                for ($i = 0, $n = count($toks); $i < $n; $i++) {
                    $uni[$toks[$i]] = ($uni[$toks[$i]] ?? 0) + 1;
                    $total++;
                    if ($i + 1 < $n) {
                        $key = $toks[$i] . "\u{00B7}" . $toks[$i + 1];
                        $bi[$key] = ($bi[$key] ?? 0) + 1;
                    }
                }
            }
        } finally {
            self::$active = $keep;
        }
        $haveBase = is_file(__DIR__ . '/../Lexicon/th_base.txt');
        $out = [];
        foreach ($bi as $key => $c) {
            if ($c < $minCount) { continue; }
            [$a, $b] = explode("\u{00B7}", $key, 2);
            $w = $a . $b;
            if (!preg_match('/^\p{Thai}+$/u', $w) || mb_strlen($w) < 4 || mb_strlen($w) > 16 || isset($have['words'][$w]) || isset($have['split'][$w])) { continue; }
            // both halves must be words themselves: a half like "กํา" in ออกกํา+ลัง is a piece of a longer word, not a word (without the general word list: pieces of three letters or more)
            $complete = static fn (string $x) => isset($have['words'][$x]) || (!$haveBase && mb_strlen($x) >= 3);
            if (!$complete($a) || !$complete($b)) { continue; }
            $pab = $c / $total; $pa = $uni[$a] / $total; $pb = $uni[$b] / $total;
            $npmi = log($pab / ($pa * $pb)) / -log($pab);
            if ($npmi >= 0.5) { $out[] = ['word' => $w, 'count' => $c, 'score' => round($npmi, 2)]; }
        }
        // second pass, for the candidates only: if nearly always the same piece stands right before (or after) the pair, the pair is a fragment
        // of a longer word (ระ+ทบ in กระทบ, คุณพ่อ+คุณ in คุณพ่อคุณแม่), not a word
        $want = [];
        foreach ($out as $o) { $want[$o['word']] = ['l' => [], 'r' => [], 'n' => 0]; }
        self::$active = null;
        try {
            foreach ($rows as $r) {
                $toks = $tk->tokenize((string) $r->title . ' ' . mb_substr((string) $r->body, 0, 2500));
                for ($i = 0, $n = count($toks) - 1; $i < $n; $i++) {
                    $w = $toks[$i] . $toks[$i + 1];
                    if (!isset($want[$w])) { continue; }
                    $want[$w]['n']++;
                    $l = $i > 0 ? $toks[$i - 1] : '^'; $rt = $i + 2 < count($toks) ? $toks[$i + 2] : '$';
                    $want[$w]['l'][$l] = ($want[$w]['l'][$l] ?? 0) + 1;
                    $want[$w]['r'][$rt] = ($want[$w]['r'][$rt] ?? 0) + 1;
                }
            }
        } finally {
            self::$active = $keep;
        }
        $out = array_values(array_filter($out, static function ($o) use ($want) {
            $d = $want[$o['word']] ?? null;
            if (!$d || $d['n'] === 0) { return true; }
            return max($d['l']) / $d['n'] < 0.6 && max($d['r']) / $d['n'] < 0.6;
        }));
        usort($out, static fn ($x, $y) => [$y['score'] * log(1 + $y['count'])] <=> [$x['score'] * log(1 + $x['count'])]);
        return array_slice($out, 0, $limit);
    }

    private static function dirtyFile(): string
    {
        return JPATH_CACHE . '/com_aisearch_lexicon.dirty';
    }

    /** The words were edited after the index was built: the index does not know the change yet. */
    public static function markDirty(): void
    {
        @file_put_contents(self::dirtyFile(), (string) time());
    }

    public static function isDirty(): bool
    {
        return is_file(self::dirtyFile());
    }

    // ---- snapshot: the lexicon the index was built with ------------------------------------------------------------

    private static function cacheFile(): string
    {
        return JPATH_CACHE . '/com_aisearch_lexicon.bin';
    }

    /** Saves the lexicon as it is now next to the index (row 2 of the index table) and makes it the active one. */
    public static function saveSnapshot(DatabaseInterface $db, array $collected): void
    {
        $now = gmdate('Y-m-d H:i:s');
        $payload = gzcompress(serialize(['words' => array_keys($collected['words']), 'split' => array_keys($collected['split']), 'counts' => $collected['counts']]), 6);
        $db->setQuery('DELETE FROM ' . $db->quoteName('#__aisearch_index') . ' WHERE id = 2')->execute();
        $o = (object) ['id' => 2, 'data' => $payload, 'updated' => $now];
        $db->insertObject('#__aisearch_index', $o);
        @file_put_contents(self::cacheFile(), $now . "\n" . serialize(['words' => $collected['words'], 'split' => $collected['split']]), LOCK_EX);
        self::$active = new self($collected['words'], $collected['split']);
        self::$bootedFor = $now;
        @unlink(self::dirtyFile());
    }

    /** Makes the lexicon of the index the active one (called by every search and by an update of one article). */
    public static function boot(DatabaseInterface $db): void
    {
        try {
            $updated = $db->setQuery('SELECT ' . $db->quoteName('updated') . ' FROM ' . $db->quoteName('#__aisearch_index') . ' WHERE id = 2')->loadResult();
        } catch (\Throwable $e) {
            self::$active = null;
            return;
        }
        if (!$updated) {
            self::$active = null;   // an index built before lexicons: ICU alone, as it was built
            self::$bootedFor = null;
            return;
        }
        if (self::$bootedFor === $updated && self::$active) {
            return;
        }
        $file = self::cacheFile();
        if (is_file($file) && ($fh = fopen($file, 'rb'))) {
            $head = trim((string) fgets($fh));
            if ($head === $updated) {
                $d = @unserialize((string) stream_get_contents($fh), ['allowed_classes' => false]);
                fclose($fh);
                if (is_array($d) && isset($d['words'])) {
                    self::$active = new self($d['words'], $d['split'] ?? []);
                    self::$bootedFor = $updated;
                    return;
                }
            } else {
                fclose($fh);
            }
        }
        $blob = $db->setQuery('SELECT ' . $db->quoteName('data') . ' FROM ' . $db->quoteName('#__aisearch_index') . ' WHERE id = 2')->loadResult();
        $d = $blob ? @unserialize((string) @gzuncompress($blob), ['allowed_classes' => false]) : null;
        if (!is_array($d)) {
            self::$active = null;
            return;
        }
        $words = array_fill_keys($d['words'] ?? [], true);
        $split = array_fill_keys($d['split'] ?? [], true);
        @file_put_contents($file, $updated . "\n" . serialize(['words' => $words, 'split' => $split]), LOCK_EX);
        self::$active = new self($words, $split);
        self::$bootedFor = $updated;
    }

    /** Counts of the snapshot (for the dashboard), or null when the index has none. @return array<string,int>|null */
    public static function snapshotCounts(DatabaseInterface $db): ?array
    {
        try {
            $blob = $db->setQuery('SELECT ' . $db->quoteName('data') . ' FROM ' . $db->quoteName('#__aisearch_index') . ' WHERE id = 2')->loadResult();
            $d = $blob ? @unserialize((string) @gzuncompress($blob), ['allowed_classes' => false]) : null;
            return is_array($d) ? array_merge(['total' => count($d['words'] ?? [])], $d['counts'] ?? []) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
