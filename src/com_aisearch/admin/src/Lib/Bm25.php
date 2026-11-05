<?php
declare(strict_types=1);

namespace Colorpack\Component\AiSearch\Administrator\Lib;

/** In-memory BM25 (fine for ~10^4 docs; persist via serialize()/cache). */
final class Bm25
{
    /** @var array<string,array<int,int>> term => [docId => tf] */
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
            $this->postings[(string)$t][$id] = $tf;
        }
        $this->avg = array_sum($this->len) / max(1, count($this->len));
    }

    public function remove(int $id): void
    {
        unset($this->len[$id]);
        foreach ($this->postings as $t => &$p) {
            unset($p[$id]);
            if (!$p) {
                unset($this->postings[$t]);
            }
        }
        $this->avg = $this->len ? array_sum($this->len) / count($this->len) : 0.0;
    }

    /** @param string[] $queryTokens @return array<int,float> id => score, best first */
    public function search(array $queryTokens, int $k = 50): array
    {
        $n = count($this->len);
        $scores = [];
        foreach (array_unique($queryTokens) as $t) {
            $p = $this->postings[$t] ?? null;
            if (!$p) {
                continue;
            }
            $idf = log(1 + ($n - count($p) + 0.5) / (count($p) + 0.5));
            foreach ($p as $id => $tf) {
                $norm = $tf + $this->k1 * (1 - $this->b + $this->b * $this->len[$id] / ($this->avg ?: 1));
                $scores[$id] = ($scores[$id] ?? 0) + $idf * $tf * ($this->k1 + 1) / $norm;
            }
        }
        arsort($scores);
        return array_slice($scores, 0, $k, true);
    }
}
