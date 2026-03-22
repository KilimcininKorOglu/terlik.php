<?php

declare(strict_types=1);

namespace Terlik;

use Terlik\Lang\Registry;

/**
 * Multi-language profanity detection and filtering engine.
 *
 * Resolves language config from the built-in registry.
 * All four languages (TR, EN, ES, DE) are included.
 *
 * @example
 * ```php
 * $terlik = new Terlik();
 * $terlik->containsProfanity("siktir"); // true
 * $terlik->clean("siktir git");         // "****** git"
 * ```
 */
class Terlik extends TerlikCore
{
    public function __construct(?TerlikOptions $options = null)
    {
        $lang = $options?->language ?? 'tr';
        $langConfig = Registry::getLanguageConfig($lang);
        parent::__construct($langConfig, $options);
    }

    /**
     * Creates and warms instances for multiple languages at once.
     * Useful for server deployments to eliminate cold-start latency.
     *
     * @param string[]|null     $languages   Language codes (defaults to all supported).
     * @param TerlikOptions|null $baseOptions Shared options applied to all instances.
     * @return array<string, Terlik> Map of language code to warmed-up instance.
     */
    public static function warmup(?array $languages = null, ?TerlikOptions $baseOptions = null): array
    {
        $langs = $languages ?? Registry::getSupportedLanguages();
        $map = [];

        foreach ($langs as $lang) {
            $instance = new self(new TerlikOptions(
                language: $lang,
                mode: $baseOptions?->mode ?? Mode::Balanced,
                maskStyle: $baseOptions?->maskStyle ?? MaskStyle::Stars,
                customList: $baseOptions?->customList,
                whitelist: $baseOptions?->whitelist,
                enableFuzzy: $baseOptions?->enableFuzzy ?? false,
                fuzzyThreshold: $baseOptions?->fuzzyThreshold ?? 0.8,
                fuzzyAlgorithm: $baseOptions?->fuzzyAlgorithm ?? FuzzyAlgorithm::Levenshtein,
                maxLength: $baseOptions?->maxLength ?? Utils::MAX_INPUT_LENGTH,
                replaceMask: $baseOptions?->replaceMask ?? '[***]',
                disableLeetDecode: $baseOptions?->disableLeetDecode ?? false,
                disableCompound: $baseOptions?->disableCompound ?? false,
                minSeverity: $baseOptions?->minSeverity,
                excludeCategories: $baseOptions?->excludeCategories,
                extendDictionary: $baseOptions?->extendDictionary,
            ));
            // JIT warmup
            $instance->containsProfanity('warmup');
            $map[$lang] = $instance;
        }

        return $map;
    }
}
