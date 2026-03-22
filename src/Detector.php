<?php

declare(strict_types=1);

namespace Terlik;

use Terlik\Dictionary\Dictionary;

final class Detector
{
    /** @var array<string, CompiledPattern[]> Static cache: shares patterns across instances. */
    private static array $patternCache = [];

    private Dictionary $dictionary;

    /** @var CompiledPattern[]|null */
    private ?array $patterns = null;

    private ?string $cacheKey;

    /** @var array<string, true> Normalized word set for strict/fuzzy lookups. */
    private array $normalizedWordSet = [];

    /** @var array<string, string> Normalized word → original root mapping. */
    private array $normalizedWordToRoot = [];

    /** @var callable(string): string */
    private $normalizeFn;

    /** @var callable(string): string */
    private $safeNormalizeFn;

    private string $locale;

    /** @var array<string, string> */
    private array $charClasses;

    /**
     * @param callable(string): string $normalizeFn
     * @param callable(string): string $safeNormalizeFn
     * @param array<string, string>    $charClasses
     */
    public function __construct(
        Dictionary $dictionary,
        callable $normalizeFn,
        callable $safeNormalizeFn,
        string $locale,
        array $charClasses,
        ?string $cacheKey = null,
    ) {
        $this->dictionary = $dictionary;
        $this->normalizeFn = $normalizeFn;
        $this->safeNormalizeFn = $safeNormalizeFn;
        $this->locale = $locale;
        $this->charClasses = $charClasses;
        $this->cacheKey = $cacheKey;
        $this->buildNormalizedLookup();
    }

    private function ensureCompiled(): array
    {
        if ($this->patterns === null) {
            if ($this->cacheKey !== null && isset(self::$patternCache[$this->cacheKey])) {
                $this->patterns = self::$patternCache[$this->cacheKey];

                return $this->patterns;
            }

            $this->patterns = PatternCompiler::compilePatterns(
                $this->dictionary->getEntries(),
                $this->dictionary->getSuffixes(),
                $this->charClasses,
                $this->normalizeFn,
            );

            if ($this->cacheKey !== null) {
                self::$patternCache[$this->cacheKey] = $this->patterns;
            }
        }

        return $this->patterns;
    }

    public function compile(): void
    {
        $this->ensureCompiled();
    }

    public function recompile(): void
    {
        $this->cacheKey = null;
        $this->patterns = PatternCompiler::compilePatterns(
            $this->dictionary->getEntries(),
            $this->dictionary->getSuffixes(),
            $this->charClasses,
            $this->normalizeFn,
        );
        $this->buildNormalizedLookup();
    }

    private function buildNormalizedLookup(): void
    {
        $this->normalizedWordSet = [];
        $this->normalizedWordToRoot = [];
        foreach ($this->dictionary->getAllWords() as $word) {
            $n = ($this->normalizeFn)($word);
            $this->normalizedWordSet[$n] = true;
            $this->normalizedWordToRoot[$n] = $word;
        }
    }

    /**
     * Returns compiled patterns as an associative array: root → regex.
     *
     * @return array<string, string>
     */
    public function getPatterns(): array
    {
        $map = [];
        foreach ($this->ensureCompiled() as $p) {
            $map[$p->root] = $p->regex;
        }

        return $map;
    }

    /**
     * Detects profanity in the given text.
     *
     * @return MatchResult[]
     */
    public function detect(string $text, ?DetectOptions $options = null): array
    {
        $mode = $options?->mode ?? Mode::Balanced;
        $results = [];
        $whitelist = $this->dictionary->getWhitelist();

        if ($mode === Mode::Strict) {
            $this->detectStrict($text, $whitelist, $results);
        } else {
            $this->detectPattern($text, $whitelist, $results, $options);
        }

        if ($mode === Mode::Loose || ($options?->enableFuzzy ?? false)) {
            $threshold = $options?->fuzzyThreshold ?? 0.8;
            $algorithm = $options?->fuzzyAlgorithm ?? FuzzyAlgorithm::Levenshtein;
            $this->detectFuzzy($text, $whitelist, $results, $threshold, $algorithm);
        }

        return $this->deduplicateResults(
            $this->applyStrictnessFilters($results, $options)
        );
    }

    /**
     * @param MatchResult[] $results
     * @return MatchResult[]
     */
    private function applyStrictnessFilters(array $results, ?DetectOptions $options): array
    {
        $minSev = $options?->minSeverity;
        $exCats = $options?->excludeCategories;

        if ($minSev === null && ($exCats === null || empty($exCats))) {
            return $results;
        }

        return array_values(array_filter($results, static function (MatchResult $r) use ($minSev, $exCats) {
            if ($minSev !== null && SeverityOrder::get($r->severity) < SeverityOrder::get($minSev)) {
                return false;
            }
            if ($exCats !== null && $r->category !== null && in_array($r->category, $exCats, true)) {
                return false;
            }

            return true;
        }));
    }

    /**
     * @param array<string, true> $whitelist
     * @param MatchResult[]       $results
     */
    private function detectStrict(string $text, array $whitelist, array &$results): void
    {
        $normalized = ($this->normalizeFn)($text);
        $words = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $originalWords = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $charIndex = 0;
        for ($wi = 0, $wCount = count($originalWords); $wi < $wCount; $wi++) {
            $origWord = $originalWords[$wi];
            $normWord = $words[$wi] ?? '';

            if ($normWord === '') {
                $charIndex += mb_strlen($origWord) + 1;

                continue;
            }

            if (isset($whitelist[$normWord])) {
                $charIndex += mb_strlen($origWord) + 1;

                continue;
            }

            if (isset($this->normalizedWordSet[$normWord])) {
                $dictWord = $this->normalizedWordToRoot[$normWord];
                $entry = $this->dictionary->findRootForWord($dictWord);
                if ($entry !== null) {
                    $results[] = new MatchResult(
                        word: $origWord,
                        root: $entry->root,
                        index: $charIndex,
                        severity: $entry->severity,
                        category: $entry->category !== null ? Category::tryFrom($entry->category) : null,
                        method: MatchMethod::Exact,
                    );
                }
            }

            $charIndex += mb_strlen($origWord) + 1;
        }
    }

    /**
     * @param array<string, true> $whitelist
     * @param MatchResult[]       $results
     */
    private function detectPattern(
        string $text,
        array $whitelist,
        array &$results,
        ?DetectOptions $options,
    ): void {
        $activeNormFn = ($options?->disableLeetDecode ?? false)
            ? $this->safeNormalizeFn
            : $this->normalizeFn;

        // First pass: locale-lowered text
        $lowerText = $this->localeLower($text);
        $this->runPatterns($lowerText, $text, $whitelist, $results, $lowerText !== $text, $options);

        // Second pass on normalized text
        $normalizedText = $activeNormFn($text);
        if ($normalizedText !== $lowerText && $normalizedText !== '') {
            $this->runPatterns($normalizedText, $text, $whitelist, $results, true, $options);
        }

        // Third pass: decompound CamelCase boundaries
        if (!($options?->disableCompound ?? false)) {
            $decompound = preg_replace('/([a-z])([A-Z])/', '$1 $2', $text) ?? $text;
            $decompound = preg_replace('/([A-Z]{2,})([a-z])/', '$1 $2', $decompound) ?? $decompound;

            if ($decompound !== $text) {
                $decompoundNorm = $activeNormFn($decompound);
                if ($decompoundNorm !== $normalizedText && $decompoundNorm !== $lowerText) {
                    $this->runPatterns($decompoundNorm, $text, $whitelist, $results, true, $options);
                }
            }
        }
    }

    /**
     * Locale-aware lowercase (Turkish İ/I special case).
     */
    private function localeLower(string $text): string
    {
        if ($this->locale === 'tr') {
            $text = strtr($text, ['İ' => 'i', 'I' => 'ı']);
        }

        return mb_strtolower($text);
    }

    /**
     * @param array<string, true> $whitelist
     * @param MatchResult[]       $results
     */
    private function runPatterns(
        string $searchText,
        string $originalText,
        array $whitelist,
        array &$results,
        bool $isNormalized,
        ?DetectOptions $options,
    ): void {
        $existingIndices = [];
        foreach ($results as $r) {
            $existingIndices[$r->index] = true;
        }

        $patterns = $this->ensureCompiled();
        $minSev = $options?->minSeverity;
        $exCats = $options?->excludeCategories;

        foreach ($patterns as $pattern) {
            // Pattern-level skip
            if ($minSev !== null && SeverityOrder::get($pattern->severity) < SeverityOrder::get($minSev)) {
                continue;
            }
            if ($exCats !== null && $pattern->category !== null && in_array($pattern->category, $exCats, true)) {
                continue;
            }

            $patternStart = hrtime(true);

            if (preg_match_all($pattern->regex, $searchText, $matches, PREG_OFFSET_CAPTURE) === false) {
                continue;
            }

            foreach ($matches[0] as [$matchedText, $byteOffset]) {
                // Convert byte offset to character offset
                $matchIndex = mb_strlen(substr($searchText, 0, $byteOffset));

                // Whitelist check
                if (isset($whitelist[$matchedText])) {
                    continue;
                }
                $normalizedMatch = ($this->normalizeFn)($matchedText);
                if (isset($whitelist[$normalizedMatch])) {
                    continue;
                }

                $surrounding = $this->getSurroundingWord($searchText, $byteOffset, strlen($matchedText));
                if (isset($whitelist[$surrounding])) {
                    continue;
                }
                $normalizedSurrounding = ($this->normalizeFn)($surrounding);
                if (isset($whitelist[$normalizedSurrounding])) {
                    continue;
                }

                if ($isNormalized) {
                    $mapped = $this->mapNormalizedToOriginal($originalText, $matchIndex, $matchedText);

                    if ($mapped !== null && isset($whitelist[mb_strtolower($mapped['word'])])) {
                        continue;
                    }

                    // Reject matches where the original word ends with only digits
                    if ($mapped !== null
                        && preg_match('/\d+$/', $mapped['word'])
                        && preg_match('/^[^\d]+\d+$/', $mapped['word'])
                    ) {
                        continue;
                    }

                    if ($mapped !== null && !isset($existingIndices[$mapped['index']])) {
                        $results[] = new MatchResult(
                            word: $mapped['word'],
                            root: $pattern->root,
                            index: $mapped['index'],
                            severity: $pattern->severity,
                            category: $pattern->category,
                            method: MatchMethod::Pattern,
                        );
                        $existingIndices[$mapped['index']] = true;
                    }
                } else {
                    if (!isset($existingIndices[$matchIndex])) {
                        $results[] = new MatchResult(
                            word: $matchedText,
                            root: $pattern->root,
                            index: $matchIndex,
                            severity: $pattern->severity,
                            category: $pattern->category,
                            method: MatchMethod::Pattern,
                        );
                        $existingIndices[$matchIndex] = true;
                    }
                }

                // Timeout check
                $elapsedMs = (hrtime(true) - $patternStart) / 1_000_000;
                if ($elapsedMs > PatternCompiler::REGEX_TIMEOUT_MS) {
                    break;
                }
            }
        }
    }

    /**
     * Maps a normalized match position back to the original text word.
     *
     * @return array{word: string, index: int}|null
     */
    private function mapNormalizedToOriginal(
        string $originalText,
        int $normIndex,
        string $normMatch,
    ): ?array {
        // Split preserving whitespace segments
        $segments = preg_split('/(\s+)/', $originalText, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $normOffset = 0;
        $origOffset = 0;

        foreach ($segments as $segment) {
            if (preg_match('/^\s+$/', $segment)) {
                $normOffset += 1; // normalized collapses whitespace to single space
                $origOffset += mb_strlen($segment);

                continue;
            }

            $normWord = ($this->normalizeFn)($segment);
            $normEnd = $normOffset + mb_strlen($normWord);

            if ($normIndex >= $normOffset && $normIndex < $normEnd) {
                return ['word' => $segment, 'index' => $origOffset];
            }

            $normOffset = $normEnd;
            $origOffset += mb_strlen($segment);
        }

        return null;
    }

    /**
     * @param array<string, true> $whitelist
     * @param MatchResult[]       $existingResults
     */
    private function detectFuzzy(
        string $text,
        array $whitelist,
        array &$existingResults,
        float $threshold,
        FuzzyAlgorithm $algorithm,
    ): void {
        $normalized = ($this->normalizeFn)($text);
        $normWords = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $origWords = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $matcher = Fuzzy::getMatcher($algorithm);

        $existingIndices = [];
        foreach ($existingResults as $r) {
            $existingIndices[$r->index] = true;
        }

        $startTime = hrtime(true);
        $charIndex = 0;

        for ($wi = 0, $wCount = count($origWords); $wi < $wCount; $wi++) {
            $elapsedMs = (hrtime(true) - $startTime) / 1_000_000;
            if ($elapsedMs > PatternCompiler::REGEX_TIMEOUT_MS) {
                break;
            }

            $origWord = $origWords[$wi];
            $word = $normWords[$wi] ?? '';

            if (mb_strlen($word) < 3 || isset($whitelist[$word])) {
                $charIndex += mb_strlen($origWord) + 1;

                continue;
            }

            foreach ($this->normalizedWordSet as $normDict => $_) {
                if (mb_strlen($normDict) < 3) {
                    continue;
                }

                $similarity = $matcher($word, $normDict);
                if ($similarity >= $threshold) {
                    if (!isset($existingIndices[$charIndex])) {
                        $dictWord = $this->normalizedWordToRoot[$normDict];
                        $entry = $this->dictionary->findRootForWord($dictWord);
                        if ($entry !== null) {
                            $existingResults[] = new MatchResult(
                                word: $origWord,
                                root: $entry->root,
                                index: $charIndex,
                                severity: $entry->severity,
                                category: $entry->category !== null
                                    ? Category::tryFrom($entry->category) : null,
                                method: MatchMethod::Fuzzy,
                            );
                            $existingIndices[$charIndex] = true;
                        }
                    }

                    break;
                }
            }

            $charIndex += mb_strlen($origWord) + 1;
        }
    }

    /**
     * Gets the surrounding word at a byte offset in the text.
     */
    private function getSurroundingWord(string $text, int $byteStart, int $byteLength): string
    {
        $start = $byteStart;
        $end = $byteStart + $byteLength;

        // Expand backwards while previous char is a word character
        while ($start > 0) {
            $prevChar = mb_substr(substr($text, 0, $start), -1);
            if ($prevChar === '' || !preg_match('/[a-zA-Z\x{00C0}-\x{024F}]/u', $prevChar)) {
                break;
            }
            $start -= strlen($prevChar);
        }

        // Expand forwards while next char is a word character
        $textLen = strlen($text);
        while ($end < $textLen) {
            // Get the character at byte position $end
            $remaining = substr($text, $end);
            $nextChar = mb_substr($remaining, 0, 1);
            if ($nextChar === '' || !preg_match('/[a-zA-Z\x{00C0}-\x{024F}]/u', $nextChar)) {
                break;
            }
            $end += strlen($nextChar);
        }

        return substr($text, $start, $end - $start);
    }

    /**
     * Deduplicates match results, keeping the longest match at each index.
     *
     * @param MatchResult[] $results
     * @return MatchResult[]
     */
    private function deduplicateResults(array $results): array
    {
        $seen = [];
        foreach ($results as $result) {
            $idx = $result->index;
            if (!isset($seen[$idx]) || mb_strlen($result->word) > mb_strlen($seen[$idx]->word)) {
                $seen[$idx] = $result;
            }
        }

        $values = array_values($seen);
        usort($values, static fn(MatchResult $a, MatchResult $b) => $a->index - $b->index);

        return $values;
    }
}
