<?php

declare(strict_types=1);

namespace Terlik;

use Terlik\Dictionary\Dictionary;
use Terlik\Dictionary\Schema;
use Terlik\Lang\LanguageConfig;

/**
 * Core profanity detection and filtering engine.
 * Requires a pre-resolved LanguageConfig — no registry dependency.
 *
 * For convenience with automatic language resolution, use Terlik from the main entry point.
 */
class TerlikCore
{
    private Dictionary $dictionary;
    private Detector $detector;
    private Mode $mode;
    private MaskStyle $maskStyle;
    private bool $enableFuzzy;
    private float $fuzzyThreshold;
    private FuzzyAlgorithm $fuzzyAlgorithm;
    private int $maxLength;
    private string $replaceMask;
    private bool $disableLeetDecode;
    private bool $disableCompound;
    private ?Severity $minSeverity;
    /** @var Category[]|null */
    private ?array $excludeCategories;

    /** The language code this instance was created with. */
    public readonly string $language;

    public function __construct(LanguageConfig $langConfig, ?TerlikOptions $options = null)
    {
        $this->language = $langConfig->locale;
        $this->mode = $options?->mode ?? Mode::Balanced;
        $this->maskStyle = $options?->maskStyle ?? MaskStyle::Stars;
        $this->enableFuzzy = $options?->enableFuzzy ?? false;
        $this->fuzzyAlgorithm = $options?->fuzzyAlgorithm ?? FuzzyAlgorithm::Levenshtein;
        $this->replaceMask = $options?->replaceMask ?? '[***]';
        $this->disableLeetDecode = $options?->disableLeetDecode ?? false;
        $this->disableCompound = $options?->disableCompound ?? false;
        $this->minSeverity = $options?->minSeverity;
        $this->excludeCategories = $options?->excludeCategories;

        $threshold = $options?->fuzzyThreshold ?? 0.8;
        if ($threshold < 0 || $threshold > 1) {
            throw new \InvalidArgumentException(
                sprintf('fuzzyThreshold must be between 0 and 1, got %s', $threshold)
            );
        }
        $this->fuzzyThreshold = $threshold;

        $maxLen = $options?->maxLength ?? Utils::MAX_INPUT_LENGTH;
        if ($maxLen < 1) {
            throw new \InvalidArgumentException(
                sprintf('maxLength must be at least 1, got %d', $maxLen)
            );
        }
        $this->maxLength = $maxLen;

        $normalizeFn = TextNormalizer::createNormalizer(new NormalizerConfig(
            locale: $langConfig->locale,
            charMap: $langConfig->charMap,
            leetMap: $langConfig->leetMap,
            numberExpansions: $langConfig->numberExpansions,
        ));

        $safeNormalizeFn = TextNormalizer::createNormalizer(new NormalizerConfig(
            locale: $langConfig->locale,
            charMap: $langConfig->charMap,
            leetMap: [],
            numberExpansions: [],
        ));

        $dictData = $langConfig->dictionary;
        if ($options?->extendDictionary !== null) {
            Schema::validateDictionary($options->extendDictionary);
            $dictData = Schema::mergeDictionaries($dictData, $options->extendDictionary);
        }

        $this->dictionary = new Dictionary(
            $dictData,
            $options?->customList,
            $options?->whitelist,
        );

        $hasCustomDict = !empty($options?->customList)
            || !empty($options?->whitelist)
            || $options?->extendDictionary !== null;

        $this->detector = new Detector(
            $this->dictionary,
            $normalizeFn,
            $safeNormalizeFn,
            $langConfig->locale,
            $langConfig->charClasses,
            $hasCustomDict ? null : $langConfig->locale,
        );
    }

    /**
     * Checks whether the text contains any profanity.
     */
    public function containsProfanity(string $text, ?DetectOptions $options = null): bool
    {
        $input = Utils::validateInput($text, $this->maxLength);
        if ($input === '') {
            return false;
        }

        $matches = $this->detector->detect($input, $this->mergeDetectOptions($options));

        return count($matches) > 0;
    }

    /**
     * Returns all profanity matches found in the text.
     *
     * @return MatchResult[]
     */
    public function getMatches(string $text, ?DetectOptions $options = null): array
    {
        $input = Utils::validateInput($text, $this->maxLength);
        if ($input === '') {
            return [];
        }

        return $this->detector->detect($input, $this->mergeDetectOptions($options));
    }

    /**
     * Returns the text with profanity replaced by masked versions.
     */
    public function clean(string $text, ?CleanOptions $options = null): string
    {
        $input = Utils::validateInput($text, $this->maxLength);
        if ($input === '') {
            return $input;
        }

        $matches = $this->detector->detect($input, $this->mergeDetectOptions($options));
        $style = $options?->maskStyle ?? $this->maskStyle;
        $replaceMask = $options?->replaceMask ?? $this->replaceMask;

        return Cleaner::cleanText($input, $matches, $style, $replaceMask);
    }

    /**
     * Adds words to the dictionary at runtime.
     *
     * @param string[] $words
     */
    public function addWords(array $words): void
    {
        $this->dictionary->addWords($words);
        $this->detector->recompile();
    }

    /**
     * Removes words from the dictionary at runtime.
     *
     * @param string[] $words
     */
    public function removeWords(array $words): void
    {
        $this->dictionary->removeWords($words);
        $this->detector->recompile();
    }

    /**
     * Returns compiled patterns as root → regex mapping.
     *
     * @return array<string, string>
     */
    public function getPatterns(): array
    {
        return $this->detector->getPatterns();
    }

    private function mergeDetectOptions(?DetectOptions $options): DetectOptions
    {
        return new DetectOptions(
            mode: $options?->mode ?? $this->mode,
            enableFuzzy: $options?->enableFuzzy ?? $this->enableFuzzy,
            fuzzyThreshold: $options?->fuzzyThreshold ?? $this->fuzzyThreshold,
            fuzzyAlgorithm: $options?->fuzzyAlgorithm ?? $this->fuzzyAlgorithm,
            disableLeetDecode: $options?->disableLeetDecode ?? $this->disableLeetDecode,
            disableCompound: $options?->disableCompound ?? $this->disableCompound,
            minSeverity: $options?->minSeverity ?? $this->minSeverity,
            excludeCategories: $options?->excludeCategories ?? $this->excludeCategories,
        );
    }
}
