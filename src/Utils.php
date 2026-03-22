<?php

declare(strict_types=1);

namespace Terlik;

final class Utils
{
    /** Default maximum input length (10,000 characters). */
    public const MAX_INPUT_LENGTH = 10000;

    /**
     * Validates and sanitizes text input.
     * Handles null, non-string types, and length truncation.
     */
    public static function validateInput(string $text, int $maxLength): string
    {
        if ($text === '') {
            return '';
        }

        if (mb_strlen($text) > $maxLength) {
            return mb_substr($text, 0, $maxLength);
        }

        return $text;
    }
}
