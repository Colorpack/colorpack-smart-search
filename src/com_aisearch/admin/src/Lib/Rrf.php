<?php
declare(strict_types=1);

namespace Colorpack\Component\AiSearch\Administrator\Lib;

/** Reciprocal Rank Fusion. */
final class Rrf
{
    /**
     * @param array<int,array<int,float>> $rankings list of id => score maps (best first)
     * @param float[] $weights optional per-ranking weight
     * @return array<int,float>
     */
    public static function fuse(array $rankings, int $k = 60, array $weights = []): array
    {
        $out = [];
        foreach ($rankings as $i => $ranking) {
            $w = $weights[$i] ?? 1.0;
            $rank = 0;
            foreach ($ranking as $id => $_) {
                $out[$id] = ($out[$id] ?? 0.0) + $w / ($k + ++$rank);
            }
        }
        arsort($out);
        return $out;
    }
}
