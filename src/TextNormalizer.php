<?php

declare(strict_types=1);

namespace Terlik;

final class TextNormalizer
{
    /** Invisible/zero-width characters commonly used to bypass detection (PCRE pattern). */
    private const INVISIBLE_CHARS = '/[\x{200B}\x{200C}\x{200D}\x{200E}\x{200F}\x{FEFF}\x{00AD}\x{034F}\x{2060}\x{2061}\x{2062}\x{2063}\x{2064}\x{180E}]/u';

    /** Combining diacritical marks (stripped after NFKD decomposition). */
    private const COMBINING_MARKS = '/[\x{0300}-\x{036f}]/u';

    /**
     * Cyrillic → Latin confusable map.
     * Only includes visually identical characters used for filter evasion.
     */
    private const CYRILLIC_CONFUSABLES = [
        "\u{0430}" => 'a', // Cyrillic а → Latin a
        "\u{0441}" => 'c', // Cyrillic с → Latin c
        "\u{0435}" => 'e', // Cyrillic е → Latin e
        "\u{0456}" => 'i', // Cyrillic і → Latin i (Ukrainian)
        "\u{043E}" => 'o', // Cyrillic о → Latin o
        "\u{0440}" => 'p', // Cyrillic р → Latin p
        "\u{0443}" => 'u', // Cyrillic у → Latin u
        "\u{0445}" => 'x', // Cyrillic х → Latin x
    ];

    /**
     * Turkish uppercase special handling: İ→i, I→ı
     * Must be applied BEFORE mb_strtolower for correct Turkish behavior.
     */
    private const TURKISH_UPPER_MAP = [
        'İ' => 'i',
        'I' => 'ı',
    ];

    private NormalizerConfig $config;
    private ?string $numberExpanderPattern;
    /** @var array<string, string> */
    private array $numberExpanderLookup;

    public function __construct(NormalizerConfig $config)
    {
        $this->config = $config;
        $this->numberExpanderPattern = null;
        $this->numberExpanderLookup = [];
        $this->buildNumberExpander();
    }

    private function buildNumberExpander(): void
    {
        $expansions = $this->config->numberExpansions;
        if ($expansions === null || count($expansions) === 0) {
            return;
        }

        $parts = [];
        foreach ($expansions as [$num, $replacement]) {
            $escaped = preg_quote($num, '/');
            $parts[] = '(?<=[a-zA-Z\x{00C0}-\x{024F}])' . $escaped . '(?=[a-zA-Z\x{00C0}-\x{024F}])';
            $this->numberExpanderLookup[$num] = $replacement;
        }

        $this->numberExpanderPattern = '/' . implode('|', $parts) . '/u';
    }

    /**
     * Applies the 10-stage normalization pipeline:
     *   1. Strip invisible chars (ZWSP, ZWNJ, soft hyphen, etc.)
     *   2. NFKD decompose (fullwidth → ASCII, precomposed → base + combining)
     *   3. Strip combining marks (removes accents/diacritics)
     *   4. Locale-aware lowercase
     *   5. Cyrillic confusable → Latin
     *   6. Language-specific char folding
     *   7. Number expansion
     *   8. Leet decode
     *   9. Punctuation removal
     *  10. Repeat collapse + whitespace trim
     */
    public function normalize(string $text): string
    {
        $result = $text;

        // 1. Strip invisible chars
        $result = preg_replace(self::INVISIBLE_CHARS, '', $result) ?? $result;

        // 2. NFKD decompose
        $normalized = \Normalizer::normalize($result, \Normalizer::FORM_KD);
        if ($normalized !== false) {
            $result = $normalized;
        }

        // 3. Strip combining marks
        $result = preg_replace(self::COMBINING_MARKS, '', $result) ?? $result;

        // 4. Locale-aware lowercase
        $result = $this->localeLowercase($result);

        // 5. Cyrillic confusable → Latin
        $result = self::replaceFromMap($result, self::CYRILLIC_CONFUSABLES);

        // 6. Language-specific char folding
        $result = self::replaceFromMap($result, $this->config->charMap);

        // 7. Number expansion
        if ($this->numberExpanderPattern !== null) {
            $lookup = $this->numberExpanderLookup;
            $result = preg_replace_callback(
                $this->numberExpanderPattern,
                static fn(array $match) => $lookup[$match[0]] ?? $match[0],
                $result,
            ) ?? $result;
        }

        // 8. Leet decode
        $result = self::replaceFromMap($result, $this->config->leetMap);

        // 9. Punctuation removal (between letters)
        $result = self::removePunctuation($result);

        // 10. Repeat collapse + whitespace trim
        $result = self::collapseRepeats($result);
        $result = self::trimWhitespace($result);

        return $result;
    }

    /**
     * Locale-aware lowercase.
     * For Turkish, manually handles İ→i and I→ı before mb_strtolower.
     */
    private function localeLowercase(string $text): string
    {
        if ($this->config->locale === 'tr') {
            $text = strtr($text, self::TURKISH_UPPER_MAP);
        }

        return mb_strtolower($text);
    }

    /**
     * Replaces characters from a map, iterating character by character (Unicode-safe).
     *
     * @param array<string, string> $map
     */
    public static function replaceFromMap(string $text, array $map): string
    {
        if (empty($map)) {
            return $text;
        }

        $chars = mb_str_split($text);
        $result = '';
        foreach ($chars as $ch) {
            $result .= $map[$ch] ?? $ch;
        }

        return $result;
    }

    /**
     * Removes punctuation between letters.
     */
    public static function removePunctuation(string $text): string
    {
        return preg_replace(
            '/(?<=[a-zA-Z\x{00C0}-\x{024F}])[.\-_*,;:!?]+(?=[a-zA-Z\x{00C0}-\x{024F}])/u',
            '',
            $text,
        ) ?? $text;
    }

    /**
     * Collapses 3+ repeated characters down to 1.
     */
    public static function collapseRepeats(string $text): string
    {
        return preg_replace('/(.)\1{2,}/u', '$1', $text) ?? $text;
    }

    /**
     * Trims and collapses whitespace.
     */
    public static function trimWhitespace(string $text): string
    {
        $result = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($result);
    }

    // ─── Backward-compatible Turkish defaults ────────────

    private static ?TextNormalizer $turkishInstance = null;

    /**
     * Creates/returns a shared Turkish normalizer instance.
     * Config is loaded from the Registry (single source of truth).
     */
    private static function getTurkishInstance(): self
    {
        if (self::$turkishInstance === null) {
            $langConfig = \Terlik\Lang\Registry::getLanguageConfig('tr');
            self::$turkishInstance = new self(new NormalizerConfig(
                locale: $langConfig->locale,
                charMap: $langConfig->charMap,
                leetMap: $langConfig->leetMap,
                numberExpansions: $langConfig->numberExpansions,
            ));
        }

        return self::$turkishInstance;
    }

    /**
     * Resets the Turkish singleton instance.
     * Called by Registry::resetCache() to ensure consistency.
     */
    public static function resetTurkishInstance(): void
    {
        self::$turkishInstance = null;
    }

    /**
     * Normalizes text using the default Turkish locale pipeline.
     * Shorthand for creating a normalizer with Turkish defaults.
     */
    public static function normalizeTurkish(string $text): string
    {
        return self::getTurkishInstance()->normalize($text);
    }

    /**
     * Creates a normalizer function (closure) for the given config.
     * This mirrors the JS createNormalizer() factory.
     *
     * @return callable(string): string
     */
    public static function createNormalizer(NormalizerConfig $config): callable
    {
        $instance = new self($config);

        return fn(string $text): string => $instance->normalize($text);
    }
}
