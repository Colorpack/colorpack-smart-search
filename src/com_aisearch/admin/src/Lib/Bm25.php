<?php
declare(strict_types=1);

namespace Colorpack\Component\AiSearch\Administrator\Lib;

/**
 * BM25 over an in-memory inverted index (fine for ~10^4 docs; persist via serialize()/cache).
 *
 * Postings are kept as one packed string per term (big-endian uint32 pairs: doc id, term frequency) instead of a nested
 * PHP array. That makes the saved index several times smaller and, above all, fast to load on every search request:
 * a query only unpacks the postings of its own words. Older indexes (nested arrays) still work and are converted by compact().
 */
final class Bm25
{
    /** @var array<string,string|array<int,int>> term => packed (id,tf) pairs; the old format is term => [docId => tf] */
    private array $postings = [];
    /** @var array<int,int> */
    private array $len = [];
    private float $avg = 0.0;

    public function __construct(private float $k1 = 1.2, private float $b = 0.75) {}

    /** @param string[] $tokens */
    public function add(int $id, array $tokens): void
    {
        $this->len[$id] = count($tokens);
        foreach (array_count_values($tokens) as $t => $tf) {
            $t = (string) $t;
            if (!isset($this->postings[$t])) {
                $this->postings[$t] = '';
            } elseif (is_array($this->postings[$t])) {
                $this->postings[$t] = self::pack($this->postings[$t]);
            }
            $this->postings[$t] .= pack('NN', $id, $tf);   // appended in place: building the index of a big site must not copy every list again and again
        }
        $this->avg = array_sum($this->len) / max(1, count($this->len));
    }

    public function remove(int $id): void
    {
        unset($this->len[$id]);
        $needle = pack('N', $id);
        foreach ($this->postings as $t => $p) {
            if (is_array($p)) {
                unset($p[$id]);
                $p = self::pack($p);
            } else {
                // pairs are 8 bytes: only a hit on an 8-byte boundary is a doc id
                $off = 0;
                while (($pos = strpos($p, $needle, $off)) !== false) {
                    if ($pos % 8 === 0) {
                        $p = substr($p, 0, $pos) . substr($p, $pos + 8);
                        $off = $pos;
                    } else {
                        $off = $pos + 1;
                    }
                }
            }
            if ($p === '') {
                unset($this->postings[$t]);
            } else {
                $this->postings[$t] = $p;
            }
        }
        $this->avg = $this->len ? array_sum($this->len) / count($this->len) : 0.0;
    }

    /** Number of articles in the index. */
    public function docCount(): int
    {
        return count($this->len);
    }

    /** In how many articles a word appears (used to tell very common words from the words that carry the topic). */
    public function docFreq(string $term): int
    {
        $p = $this->postings[$term] ?? null;
        if ($p === null || $p === '' || $p === []) {
            return 0;
        }
        return is_array($p) ? count($p) : intdiv(strlen($p), 8);
    }

    /** True when every term is already in the packed format. */
    public function isCompact(): bool
    {
        foreach ($this->postings as $p) {
            return !is_array($p);   // an index is built in one format, so the first term tells
        }
        return true;
    }

    /** Converts an index saved in the old nested-array format. */
    public function compact(): void
    {
        foreach ($this->postings as $t => $p) {
            if (is_array($p)) {
                $this->postings[$t] = self::pack($p);
            }
        }
    }

    /** @param array<int,int> $idToTf */
    private static function pack(array $idToTf): string
    {
        $s = '';
        foreach ($idToTf as $id => $tf) {
            $s .= pack('NN', $id, $tf);
        }
        return $s;
    }

    /** @param string[] $queryTokens @return array<int,float> id => score, best first */
    public function search(array $queryTokens, int $k = 50): array
    {
        $n = count($this->len);
        $scores = [];
        $avg = $this->avg ?: 1;
        foreach (array_unique($queryTokens) as $t) {
            $p = $this->postings[$t] ?? null;
            if ($p === null || $p === '' || $p === []) {
                continue;
            }
            $pairs = is_array($p) ? $p : $this->unpackPairs($p);
            $df = count($pairs);
            $idf = log(1 + ($n - $df + 0.5) / ($df + 0.5));
            foreach ($pairs as $id => $tf) {
                $norm = $tf + $this->k1 * (1 - $this->b + $this->b * $this->len[$id] / $avg);
                $scores[$id] = ($scores[$id] ?? 0) + $idf * $tf * ($this->k1 + 1) / $norm;
            }
        }
        arsort($scores);
        return array_slice($scores, 0, $k, true);
    }

    /** @return array<int,int> docId => tf */
    private function unpackPairs(string $p): array
    {
        $u = unpack('N*', $p);
        $out = [];
        for ($i = 1, $c = count($u); $i < $c; $i += 2) {
            $out[$u[$i]] = $u[$i + 1];
        }
        return $out;
    }
}
