<?php

declare(strict_types=1);

namespace Terlik\Dictionary;

final class Schema
{
    private const VALID_SEVERITIES = ['high', 'medium', 'low'];
    private const VALID_CATEGORIES = ['sexual', 'insult', 'slur', 'general'];
    private const MAX_SUFFIXES = 150;
    private const SUFFIX_PATTERN = '/^\p{Ll}{1,10}$/u';

    /**
     * Validates raw dictionary data against the expected schema.
     *
     * @param array<string, mixed> $data The raw data to validate (typically parsed from JSON).
     * @return array<string, mixed> The validated dictionary data.
     * @throws \InvalidArgumentException If validation fails.
     */
    public static function validateDictionary(array $data): array
    {
        if (!isset($data['version']) || !is_numeric($data['version']) || $data['version'] < 1) {
            throw new \InvalidArgumentException('Dictionary version must be a positive number');
        }

        // Validate suffixes
        if (!isset($data['suffixes']) || !is_array($data['suffixes'])) {
            throw new \InvalidArgumentException('Dictionary suffixes must be an array');
        }

        if (count($data['suffixes']) > self::MAX_SUFFIXES) {
            throw new \InvalidArgumentException(
                sprintf('Dictionary suffixes exceed maximum of %d', self::MAX_SUFFIXES)
            );
        }

        foreach ($data['suffixes'] as $suffix) {
            if (!is_string($suffix) || !preg_match(self::SUFFIX_PATTERN, $suffix)) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid suffix "%s": must be 1-10 lowercase Unicode letters', $suffix)
                );
            }
        }

        // Validate entries
        if (!isset($data['entries']) || !is_array($data['entries'])) {
            throw new \InvalidArgumentException('Dictionary entries must be an array');
        }

        $seenRoots = [];
        foreach ($data['entries'] as $i => $entry) {
            $label = sprintf('entries[%d]', $i);

            if (!is_array($entry)) {
                throw new \InvalidArgumentException(sprintf('%s: must be an object', $label));
            }

            if (!isset($entry['root']) || !is_string($entry['root']) || $entry['root'] === '') {
                throw new \InvalidArgumentException(sprintf('%s: root must be a non-empty string', $label));
            }

            $rootLower = mb_strtolower($entry['root']);
            if (isset($seenRoots[$rootLower])) {
                throw new \InvalidArgumentException(
                    sprintf('%s: duplicate root "%s"', $label, $entry['root'])
                );
            }
            $seenRoots[$rootLower] = true;

            if (!isset($entry['variants']) || !is_array($entry['variants'])) {
                throw new \InvalidArgumentException(
                    sprintf('%s (root="%s"): variants must be an array', $label, $entry['root'])
                );
            }

            if (!isset($entry['severity']) || !is_string($entry['severity'])
                || !in_array($entry['severity'], self::VALID_SEVERITIES, true)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        '%s (root="%s"): severity must be one of %s',
                        $label,
                        $entry['root'],
                        implode(', ', self::VALID_SEVERITIES),
                    )
                );
            }

            if (!isset($entry['category']) || !is_string($entry['category'])
                || !in_array($entry['category'], self::VALID_CATEGORIES, true)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        '%s (root="%s"): category must be one of %s',
                        $label,
                        $entry['root'],
                        implode(', ', self::VALID_CATEGORIES),
                    )
                );
            }

            if (!isset($entry['suffixable']) || !is_bool($entry['suffixable'])) {
                throw new \InvalidArgumentException(
                    sprintf('%s (root="%s"): suffixable must be a boolean', $label, $entry['root'])
                );
            }
        }

        // Validate whitelist
        if (!isset($data['whitelist']) || !is_array($data['whitelist'])) {
            throw new \InvalidArgumentException('Dictionary whitelist must be an array');
        }

        $seenWhitelist = [];
        foreach ($data['whitelist'] as $i => $item) {
            if (!is_string($item)) {
                throw new \InvalidArgumentException(sprintf('whitelist[%d]: must be a string', $i));
            }
            if ($item === '') {
                throw new \InvalidArgumentException(sprintf('whitelist[%d]: must not be empty', $i));
            }
            $wlLower = mb_strtolower($item);
            if (isset($seenWhitelist[$wlLower])) {
                throw new \InvalidArgumentException(
                    sprintf('whitelist[%d]: duplicate entry "%s"', $i, $item)
                );
            }
            $seenWhitelist[$wlLower] = true;
        }

        return $data;
    }

    /**
     * Merges an extension dictionary into a base dictionary.
     *
     * @param array<string, mixed> $base The base (built-in) dictionary data.
     * @param array<string, mixed> $ext  The extension dictionary data.
     * @return array<string, mixed> A new merged dictionary data.
     */
    public static function mergeDictionaries(array $base, array $ext): array
    {
        $existingRoots = [];
        foreach ($base['entries'] as $entry) {
            $existingRoots[mb_strtolower($entry['root'])] = true;
        }

        $mergedEntries = $base['entries'];
        foreach ($ext['entries'] as $entry) {
            $rootLower = mb_strtolower($entry['root']);
            if (!isset($existingRoots[$rootLower])) {
                $mergedEntries[] = $entry;
                $existingRoots[$rootLower] = true;
            }
        }

        $mergedSuffixes = array_values(array_unique(
            array_merge($base['suffixes'], $ext['suffixes'])
        ));

        $mergedWhitelist = array_values(array_unique(
            array_merge($base['whitelist'], $ext['whitelist'])
        ));

        return [
            'version' => $base['version'],
            'suffixes' => $mergedSuffixes,
            'entries' => $mergedEntries,
            'whitelist' => $mergedWhitelist,
        ];
    }
}
