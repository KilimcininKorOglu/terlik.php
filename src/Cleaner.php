<?php

declare(strict_types=1);

namespace Terlik;

final class Cleaner
{
    private static function maskStars(string $word): string
    {
        return str_repeat('*', mb_strlen($word));
    }

    private static function maskPartial(string $word): string
    {
        $len = mb_strlen($word);
        if ($len <= 2) {
            return str_repeat('*', $len);
        }

        return mb_substr($word, 0, 1)
            . str_repeat('*', $len - 2)
            . mb_substr($word, -1);
    }

    /**
     * Applies a mask to a single word using the specified style.
     */
    public static function applyMask(string $word, MaskStyle $style, string $replaceMask): string
    {
        return match ($style) {
            MaskStyle::Stars => self::maskStars($word),
            MaskStyle::Partial => self::maskPartial($word),
            MaskStyle::Replace => $replaceMask,
        };
    }

    /**
     * Replaces all matched profanity in the text with masked versions.
     * Processes matches from end to start to preserve character indices.
     *
     * @param MatchResult[] $matches
     */
    public static function cleanText(
        string $text,
        array $matches,
        MaskStyle $style,
        string $replaceMask,
    ): string {
        if (empty($matches)) {
            return $text;
        }

        // Sort by index descending so we can replace from end to start
        $sorted = $matches;
        usort($sorted, static fn(MatchResult $a, MatchResult $b) => $b->index - $a->index);

        $result = $text;
        foreach ($sorted as $match) {
            $masked = self::applyMask($match->word, $style, $replaceMask);
            $before = mb_substr($result, 0, $match->index);
            $after = mb_substr($result, $match->index + mb_strlen($match->word));
            $result = $before . $masked . $after;
        }

        return $result;
    }
}
