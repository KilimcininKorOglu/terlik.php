<?php

declare(strict_types=1);

namespace Terlik;

/**
 * Compiles dictionary entries into regex patterns for profanity detection.
 */
final class PatternCompiler
{
    // Explicit Latin + Turkish + European letter/digit range (À = U+00C0, ɏ = U+024F).
    private const WORD_CHAR = 'a-zA-Z0-9\x{00C0}-\x{024F}';
    private const SEPARATOR_PATTERN = '[^a-zA-Z0-9\x{00C0}-\x{024F}]{0,3}';
    private const WORD_BOUNDARY_BEHIND = '(?<![a-zA-Z0-9\x{00C0}-\x{024F}])';
    private const WORD_BOUNDARY_AHEAD = '(?![a-zA-Z0-9\x{00C0}-\x{024F}])';

    private const MAX_PATTERN_LENGTH = 20000;
    private const MAX_SUFFIX_CHAIN = 2;

    /** Safety timeout (ms) for regex execution in detection loops. */
    public const REGEX_TIMEOUT_MS = 250;

    /**
     * Converts a single character to a regex pattern using charClasses.
     *
     * @param array<string, string> $charClasses
     */
    /** @var array<string, bool> Cache for validated charClass patterns. */
    private static array $validatedCharClasses = [];

    /**
     * Clears the charClass validation cache.
     * Called by Detector::clearPatternCache() during reset.
     */
    public static function resetValidationCache(): void
    {
        self::$validatedCharClasses = [];
    }

    private static function charToPattern(string $ch, array $charClasses): string
    {
        $lower = mb_strtolower($ch);
        if (isset($charClasses[$lower])) {
            $cls = $charClasses[$lower];

            // Validate charClass is a well-formed regex fragment (cached)
            if (!isset(self::$validatedCharClasses[$cls])) {
                self::$validatedCharClasses[$cls] = (@preg_match('/' . $cls . '/u', '') !== false);
            }

            if (self::$validatedCharClasses[$cls]) {
                return $cls . '+';
            }

            // Malformed charClass — fall through to literal pattern
        }

        return preg_quote($ch, '/') . '+';
    }

    /**
     * Converts a word to a regex pattern with separators between chars.
     *
     * @param array<string, string> $charClasses
     * @param callable(string): string $normalizeFn
     */
    private static function wordToPattern(
        string $word,
        array $charClasses,
        callable $normalizeFn,
    ): string {
        $normalized = $normalizeFn($word);
        $chars = mb_str_split($normalized);
        $parts = array_map(
            static fn(string $ch) => self::charToPattern($ch, $charClasses),
            $chars,
        );

        return implode(self::SEPARATOR_PATTERN, $parts);
    }

    /**
     * Suffix-only char pattern: literal char + repetition, NO leet charClasses.
     */
    private static function charToLiteralPattern(string $ch): string
    {
        return preg_quote($ch, '/') . '+';
    }

    /**
     * Builds a regex alternation group for grammatical suffixes.
     *
     * @param string[] $suffixes
     * @param array<string, string> $charClasses (unused, kept for API compat)
     */
    private static function buildSuffixGroup(array $suffixes, array $charClasses): string
    {
        if (empty($suffixes)) {
            return '';
        }

        $suffixPatterns = [];
        foreach ($suffixes as $suffix) {
            $chars = mb_str_split($suffix);
            $parts = array_map(
                static fn(string $ch) => self::charToLiteralPattern($ch),
                $chars,
            );
            $suffixPatterns[] = implode(self::SEPARATOR_PATTERN, $parts);
        }

        // Sort by length descending so longer suffixes match first
        usort($suffixPatterns, static fn(string $a, string $b) => strlen($b) - strlen($a));

        return '(?:' . self::SEPARATOR_PATTERN . '(?:' . implode('|', $suffixPatterns) . '))';
    }

    /**
     * Compiles dictionary entries into regex patterns for profanity detection.
     *
     * @param array<string, WordEntry> $entries
     * @param string[]|null            $suffixes
     * @param array<string, string>    $charClasses
     * @param callable(string): string $normalizeFn
     * @return CompiledPattern[]
     */
    public static function compilePatterns(
        array $entries,
        ?array $suffixes,
        array $charClasses,
        callable $normalizeFn,
    ): array {
        $patterns = [];

        // Build suffix group once, shared across all suffixable entries
        $suffixGroup = ($suffixes !== null && count($suffixes) > 0)
            ? self::buildSuffixGroup($suffixes, $charClasses)
            : '';

        foreach ($entries as $entry) {
            $allForms = array_merge([$entry->root], $entry->variants);

            // Normalize, filter empty, deduplicate, sort by length desc
            $sortedForms = array_map($normalizeFn, $allForms);
            $sortedForms = array_filter($sortedForms, static fn(string $w) => $w !== '');
            $sortedForms = array_values(array_unique($sortedForms));
            usort($sortedForms, static fn(string $a, string $b) => mb_strlen($b) - mb_strlen($a));

            $useSuffix = $entry->suffixable && $suffixGroup !== '';

            if ($useSuffix) {
                // Fully suffixable: all forms get suffix chain
                $formPatterns = array_map(
                    static fn(string $w) => self::wordToPattern($w, $charClasses, $normalizeFn),
                    $sortedForms,
                );
                $combined = implode('|', $formPatterns);
                $pattern = self::WORD_BOUNDARY_BEHIND
                    . '(?:' . $combined . ')'
                    . $suffixGroup . '{0,' . self::MAX_SUFFIX_CHAIN . '}'
                    . self::WORD_BOUNDARY_AHEAD;
            } elseif ($suffixGroup !== '') {
                // Non-suffixable root but has variants
                $minVariantSuffixLen = 4;
                $strictForms = [];
                $suffixableForms = [];

                foreach ($sortedForms as $w) {
                    if (mb_strlen($w) >= $minVariantSuffixLen) {
                        $suffixableForms[] = self::wordToPattern($w, $charClasses, $normalizeFn);
                    } else {
                        $strictForms[] = self::wordToPattern($w, $charClasses, $normalizeFn);
                    }
                }

                $parts = [];
                if (!empty($suffixableForms)) {
                    $parts[] = '(?:' . implode('|', $suffixableForms) . ')'
                        . $suffixGroup . '{0,' . self::MAX_SUFFIX_CHAIN . '}';
                }
                if (!empty($strictForms)) {
                    $parts[] = '(?:' . implode('|', $strictForms) . ')';
                }

                $pattern = self::WORD_BOUNDARY_BEHIND
                    . '(?:' . implode('|', $parts) . ')'
                    . self::WORD_BOUNDARY_AHEAD;
            } else {
                // No suffix group available: strict word boundary
                $formPatterns = array_map(
                    static fn(string $w) => self::wordToPattern($w, $charClasses, $normalizeFn),
                    $sortedForms,
                );
                $combined = implode('|', $formPatterns);
                $pattern = self::WORD_BOUNDARY_BEHIND
                    . '(?:' . $combined . ')'
                    . self::WORD_BOUNDARY_AHEAD;
            }

            // Safety guard: if pattern is too long, fallback to non-suffix version
            if (strlen($pattern) > self::MAX_PATTERN_LENGTH) {
                $formPatterns = array_map(
                    static fn(string $w) => self::wordToPattern($w, $charClasses, $normalizeFn),
                    $sortedForms,
                );
                $combined = implode('|', $formPatterns);
                $pattern = self::WORD_BOUNDARY_BEHIND
                    . '(?:' . $combined . ')'
                    . self::WORD_BOUNDARY_AHEAD;
            }

            $fullPattern = '/' . $pattern . '/iu';

            // Test if the pattern compiles successfully
            if (@preg_match($fullPattern, '') === false) {
                // Fallback: try without suffix
                if ($useSuffix) {
                    $fallbackForms = array_map(
                        static fn(string $w) => self::wordToPattern($w, $charClasses, $normalizeFn),
                        $sortedForms,
                    );
                    $fallbackPattern = self::WORD_BOUNDARY_BEHIND
                        . '(?:' . implode('|', $fallbackForms) . ')'
                        . self::WORD_BOUNDARY_AHEAD;
                    $fallbackFull = '/' . $fallbackPattern . '/iu';

                    if (@preg_match($fallbackFull, '') !== false) {
                        $patterns[] = new CompiledPattern(
                            root: $entry->root,
                            severity: $entry->severity,
                            category: $entry->category,
                            regex: $fallbackFull,
                            variants: $entry->variants,
                        );
                    }
                }

                continue;
            }

            $patterns[] = new CompiledPattern(
                root: $entry->root,
                severity: $entry->severity,
                category: $entry->category,
                regex: $fullPattern,
                variants: $entry->variants,
            );
        }

        return $patterns;
    }
}
