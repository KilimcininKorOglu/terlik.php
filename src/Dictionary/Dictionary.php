<?php

declare(strict_types=1);

namespace Terlik\Dictionary;

use Terlik\Category;
use Terlik\Severity;
use Terlik\WordEntry;

/**
 * Manages the profanity word list, whitelist, and suffixes for a language.
 */
final class Dictionary
{
    /** @var array<string, WordEntry> */
    private array $entries = [];

    /** @var array<string, true> */
    private array $whitelist = [];

    /** @var string[] */
    private array $allWords = [];

    /** @var string[] */
    private array $suffixes;

    /**
     * Creates a new Dictionary from validated dictionary data.
     *
     * @param array<string, mixed> $data            Validated dictionary data.
     * @param string[]|null        $customWords     Additional words to detect.
     * @param string[]|null        $customWhitelist Additional words to exclude.
     */
    public function __construct(array $data, ?array $customWords = null, ?array $customWhitelist = null)
    {
        // Initialize whitelist
        foreach ($data['whitelist'] as $w) {
            $this->whitelist[mb_strtolower($w)] = true;
        }
        $this->suffixes = $data['suffixes'];

        if ($customWhitelist !== null) {
            foreach ($customWhitelist as $w) {
                $this->whitelist[mb_strtolower($w)] = true;
            }
        }

        // Add dictionary entries
        foreach ($data['entries'] as $entry) {
            $this->addEntry(new WordEntry(
                root: $entry['root'],
                variants: $entry['variants'],
                severity: Severity::from($entry['severity']),
                category: isset($entry['category']) ? Category::tryFrom($entry['category']) : null,
                suffixable: $entry['suffixable'] ?? false,
            ));
        }

        // Add custom words
        if ($customWords !== null) {
            foreach ($customWords as $word) {
                $this->addEntry(new WordEntry(
                    root: mb_strtolower($word),
                    variants: [],
                    severity: Severity::Medium,
                ));
            }
        }
    }

    private function addEntry(WordEntry $entry): void
    {
        $normalizedRoot = mb_strtolower($entry->root);
        $this->entries[$normalizedRoot] = $entry;
        $this->allWords[] = $normalizedRoot;
        foreach ($entry->variants as $v) {
            $this->allWords[] = mb_strtolower($v);
        }
    }

    /**
     * Returns all dictionary entries keyed by root word.
     *
     * @return array<string, WordEntry>
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * Returns all words (roots + variants) as a flat array.
     *
     * @return string[]
     */
    public function getAllWords(): array
    {
        return $this->allWords;
    }

    /**
     * Returns the whitelist as an associative array (word → true).
     *
     * @return array<string, true>
     */
    public function getWhitelist(): array
    {
        return $this->whitelist;
    }

    /**
     * Returns available grammatical suffixes for the language.
     *
     * @return string[]
     */
    public function getSuffixes(): array
    {
        return $this->suffixes;
    }

    /**
     * Adds words to the dictionary at runtime.
     *
     * @param string[] $words
     */
    public function addWords(array $words): void
    {
        foreach ($words as $word) {
            $lower = trim(mb_strtolower($word));
            if ($lower === '') {
                continue;
            }
            if (!isset($this->entries[$lower])) {
                $this->addEntry(new WordEntry(
                    root: $lower,
                    variants: [],
                    severity: Severity::Medium,
                ));
            }
        }
    }

    /**
     * Removes words from the dictionary at runtime.
     *
     * @param string[] $words
     */
    public function removeWords(array $words): void
    {
        foreach ($words as $word) {
            $key = mb_strtolower($word);
            if (isset($this->entries[$key])) {
                $entry = $this->entries[$key];
                unset($this->entries[$key]);

                $variantsLower = array_map('mb_strtolower', $entry->variants);
                $this->allWords = array_values(array_filter(
                    $this->allWords,
                    static fn(string $w) => $w !== $key && !in_array($w, $variantsLower, true),
                ));
            }
        }
    }

    /**
     * Finds the dictionary entry for a given word (checks root and variants).
     */
    public function findRootForWord(string $word): ?WordEntry
    {
        $lower = mb_strtolower($word);

        if (isset($this->entries[$lower])) {
            return $this->entries[$lower];
        }

        foreach ($this->entries as $entry) {
            foreach ($entry->variants as $v) {
                if (mb_strtolower($v) === $lower) {
                    return $entry;
                }
            }
        }

        return null;
    }
}
