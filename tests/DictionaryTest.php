<?php

declare(strict_types=1);

namespace Terlik\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Terlik\Dictionary\Schema;

final class DictionaryTest extends TestCase
{
    private array $dictionary;

    protected function setUp(): void
    {
        $jsonPath = __DIR__ . '/../src/Lang/Tr/dictionary.json';
        $this->dictionary = json_decode(
            file_get_contents($jsonPath),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    // ─── dictionary JSON schema ─────────────────────────────────

    #[Test]
    public function testTrDictionaryValidatesWithoutErrors(): void
    {
        $result = Schema::validateDictionary($this->dictionary);
        $this->assertIsArray($result);
    }

    #[Test]
    public function testTrDictionaryHasValidVersion(): void
    {
        $this->assertArrayHasKey('version', $this->dictionary);
        $this->assertGreaterThanOrEqual(1, $this->dictionary['version']);
    }

    #[Test]
    public function testTrDictionaryHasEntriesArray(): void
    {
        $this->assertArrayHasKey('entries', $this->dictionary);
        $this->assertIsArray($this->dictionary['entries']);
        $this->assertGreaterThan(0, count($this->dictionary['entries']));
    }

    #[Test]
    public function testTrDictionaryHasWhitelistArray(): void
    {
        $this->assertArrayHasKey('whitelist', $this->dictionary);
        $this->assertIsArray($this->dictionary['whitelist']);
        $this->assertGreaterThan(0, count($this->dictionary['whitelist']));
    }

    #[Test]
    public function testTrDictionaryHasSuffixesArray(): void
    {
        $this->assertArrayHasKey('suffixes', $this->dictionary);
        $this->assertIsArray($this->dictionary['suffixes']);
    }

    // ─── entries ────────────────────────────────────────────────

    #[Test]
    public function testEveryEntryHasNonEmptyRoot(): void
    {
        foreach ($this->dictionary['entries'] as $i => $entry) {
            $this->assertArrayHasKey('root', $entry, "entries[$i] missing root");
            $this->assertIsString($entry['root'], "entries[$i] root is not a string");
            $this->assertNotEmpty($entry['root'], "entries[$i] root is empty");
        }
    }

    #[Test]
    public function testNoDuplicateRootsCaseInsensitive(): void
    {
        $seen = [];
        foreach ($this->dictionary['entries'] as $i => $entry) {
            $lower = mb_strtolower($entry['root']);
            $this->assertArrayNotHasKey(
                $lower,
                $seen,
                "Duplicate root '$lower' found at entries[$i]",
            );
            $seen[$lower] = true;
        }
    }

    #[Test]
    public function testEveryEntryHasValidSeverity(): void
    {
        $valid = ['high', 'medium', 'low'];
        foreach ($this->dictionary['entries'] as $i => $entry) {
            $this->assertContains(
                $entry['severity'],
                $valid,
                "entries[$i] has invalid severity '{$entry['severity']}'",
            );
        }
    }

    #[Test]
    public function testEveryEntryHasValidCategory(): void
    {
        $valid = ['sexual', 'insult', 'slur', 'general'];
        foreach ($this->dictionary['entries'] as $i => $entry) {
            $this->assertContains(
                $entry['category'],
                $valid,
                "entries[$i] has invalid category '{$entry['category']}'",
            );
        }
    }

    #[Test]
    public function testEveryEntryHasBooleanSuffixable(): void
    {
        foreach ($this->dictionary['entries'] as $i => $entry) {
            $this->assertArrayHasKey('suffixable', $entry, "entries[$i] missing suffixable");
            $this->assertIsBool($entry['suffixable'], "entries[$i] suffixable is not boolean");
        }
    }

    #[Test]
    public function testEveryEntryHasVariantsArray(): void
    {
        foreach ($this->dictionary['entries'] as $i => $entry) {
            $this->assertArrayHasKey('variants', $entry, "entries[$i] missing variants");
            $this->assertIsArray($entry['variants'], "entries[$i] variants is not an array");
        }
    }

    // ─── whitelist integrity ────────────────────────────────────

    #[Test]
    public function testWhitelistContainsKnownSafeWords(): void
    {
        $knownSafe = ['amsterdam', 'sikke', 'bokser', 'malzeme', 'memur'];
        $whitelist = array_map('mb_strtolower', $this->dictionary['whitelist']);

        foreach ($knownSafe as $word) {
            $this->assertContains(
                $word,
                $whitelist,
                "Whitelist should contain '$word'",
            );
        }
    }

    // ─── validateDictionary rejection ───────────────────────────

    #[Test]
    public function testRejectsEmptyArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Schema::validateDictionary([]);
    }

    #[Test]
    public function testRejectsMissingVersion(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Schema::validateDictionary([
            'suffixes' => [],
            'entries' => [],
            'whitelist' => [],
        ]);
    }

    #[Test]
    public function testRejectsDuplicateRoots(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Schema::validateDictionary([
            'version' => 1,
            'suffixes' => [],
            'entries' => [
                ['root' => 'test', 'variants' => [], 'severity' => 'high', 'category' => 'general', 'suffixable' => false],
                ['root' => 'test', 'variants' => [], 'severity' => 'high', 'category' => 'general', 'suffixable' => false],
            ],
            'whitelist' => [],
        ]);
    }

    #[Test]
    public function testRejectsInvalidSeverity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Schema::validateDictionary([
            'version' => 1,
            'suffixes' => [],
            'entries' => [
                ['root' => 'test', 'variants' => [], 'severity' => 'extreme', 'category' => 'general', 'suffixable' => false],
            ],
            'whitelist' => [],
        ]);
    }

    #[Test]
    public function testRejectsInvalidCategory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Schema::validateDictionary([
            'version' => 1,
            'suffixes' => [],
            'entries' => [
                ['root' => 'test', 'variants' => [], 'severity' => 'high', 'category' => 'unknown', 'suffixable' => false],
            ],
            'whitelist' => [],
        ]);
    }

    #[Test]
    public function testRejectsEmptyRoot(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Schema::validateDictionary([
            'version' => 1,
            'suffixes' => [],
            'entries' => [
                ['root' => '', 'variants' => [], 'severity' => 'high', 'category' => 'general', 'suffixable' => false],
            ],
            'whitelist' => [],
        ]);
    }

    #[Test]
    public function testRejectsInvalidSuffixFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Schema::validateDictionary([
            'version' => 1,
            'suffixes' => ['ABC'],
            'entries' => [],
            'whitelist' => [],
        ]);
    }

    #[Test]
    public function testRejectsSuffixLongerThanTenChars(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Schema::validateDictionary([
            'version' => 1,
            'suffixes' => ['abcdefghijk'],
            'entries' => [],
            'whitelist' => [],
        ]);
    }

    #[Test]
    public function testRejectsTooManySuffixes(): void
    {
        $suffixes = [];
        for ($i = 0; $i < 151; $i++) {
            $suffixes[] = 'suf' . str_pad((string) $i, 4, 'a');
        }

        $this->expectException(\InvalidArgumentException::class);
        Schema::validateDictionary([
            'version' => 1,
            'suffixes' => $suffixes,
            'entries' => [],
            'whitelist' => [],
        ]);
    }

    #[Test]
    public function testRejectsEmptyWhitelistEntry(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Schema::validateDictionary([
            'version' => 1,
            'suffixes' => [],
            'entries' => [],
            'whitelist' => [''],
        ]);
    }

    #[Test]
    public function testRejectsDuplicateWhitelistEntry(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Schema::validateDictionary([
            'version' => 1,
            'suffixes' => [],
            'entries' => [],
            'whitelist' => ['word', 'word'],
        ]);
    }
}
