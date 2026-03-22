<?php

declare(strict_types=1);

namespace Terlik;

final class Fuzzy
{
    /**
     * Computes the Levenshtein edit distance between two strings.
     * Uses O(n) space optimization with two-row approach.
     */
    public static function levenshteinDistance(string $a, string $b): int
    {
        $aChars = mb_str_split($a);
        $bChars = mb_str_split($b);
        $m = count($aChars);
        $n = count($bChars);

        if ($m === 0) {
            return $n;
        }
        if ($n === 0) {
            return $m;
        }

        $prev = range(0, $n);
        $curr = array_fill(0, $n + 1, 0);

        for ($i = 1; $i <= $m; $i++) {
            $curr[0] = $i;
            for ($j = 1; $j <= $n; $j++) {
                $cost = ($aChars[$i - 1] === $bChars[$j - 1]) ? 0 : 1;
                $curr[$j] = min(
                    $prev[$j] + 1,       // deletion
                    $curr[$j - 1] + 1,    // insertion
                    $prev[$j - 1] + $cost // substitution
                );
            }
            [$prev, $curr] = [$curr, $prev];
        }

        return $prev[$n];
    }

    /**
     * Computes the Levenshtein similarity ratio between two strings.
     * Returns a value between 0 (completely different) and 1 (identical).
     */
    public static function levenshteinSimilarity(string $a, string $b): float
    {
        $maxLen = max(mb_strlen($a), mb_strlen($b));
        if ($maxLen === 0) {
            return 1.0;
        }

        return 1.0 - self::levenshteinDistance($a, $b) / $maxLen;
    }

    /**
     * Returns the set of bigrams (character pairs) in a string.
     *
     * @return array<string, true>
     */
    private static function bigrams(string $str): array
    {
        $chars = mb_str_split($str);
        $set = [];
        for ($i = 0, $len = count($chars) - 1; $i < $len; $i++) {
            $set[$chars[$i] . $chars[$i + 1]] = true;
        }

        return $set;
    }

    /**
     * Computes the Dice coefficient (bigram similarity) between two strings.
     * Returns a value between 0 (no shared bigrams) and 1 (identical bigrams).
     */
    public static function diceSimilarity(string $a, string $b): float
    {
        if (mb_strlen($a) < 2 || mb_strlen($b) < 2) {
            return $a === $b ? 1.0 : 0.0;
        }

        $bigramsA = self::bigrams($a);
        $bigramsB = self::bigrams($b);

        $intersection = 0;
        foreach ($bigramsA as $bg => $_) {
            if (isset($bigramsB[$bg])) {
                $intersection++;
            }
        }

        return (2 * $intersection) / (count($bigramsA) + count($bigramsB));
    }

    /**
     * Returns the appropriate fuzzy matching function for the given algorithm.
     *
     * @return callable(string, string): float
     */
    public static function getMatcher(FuzzyAlgorithm $algorithm): callable
    {
        return $algorithm === FuzzyAlgorithm::Levenshtein
            ? [self::class, 'levenshteinSimilarity']
            : [self::class, 'diceSimilarity'];
    }
}
