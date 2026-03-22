<?php

declare(strict_types=1);

namespace Terlik\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Terlik\Dictionary\Schema;
use Terlik\Terlik;
use Terlik\TerlikOptions;

final class ExtendDictionaryTest extends TestCase
{
    // ──────────────────────────────────────────────
    //  mergeDictionaries
    // ──────────────────────────────────────────────

    private function makeBase(): array
    {
        return [
            'version' => 1,
            'suffixes' => ['ler', 'lar'],
            'entries' => [
                [
                    'root' => 'wordone',
                    'variants' => ['wordonevar'],
                    'severity' => 'high',
                    'category' => 'sexual',
                    'suffixable' => true,
                ],
                [
                    'root' => 'wordtwo',
                    'variants' => [],
                    'severity' => 'medium',
                    'category' => 'insult',
                    'suffixable' => false,
                ],
            ],
            'whitelist' => ['safeone', 'safetwo'],
        ];
    }

    private function makeExtension(): array
    {
        return [
            'version' => 2,
            'suffixes' => ['lar', 'dan'],
            'entries' => [
                [
                    'root' => 'wordthree',
                    'variants' => ['wordthreevar'],
                    'severity' => 'low',
                    'category' => 'general',
                    'suffixable' => false,
                ],
                [
                    'root' => 'WordOne',
                    'variants' => ['wordonedup'],
                    'severity' => 'high',
                    'category' => 'sexual',
                    'suffixable' => true,
                ],
            ],
            'whitelist' => ['safetwo', 'safethree'],
        ];
    }

    #[Test]
    public function mergeDictionariesMergesEntriesFromExtension(): void
    {
        $merged = Schema::mergeDictionaries($this->makeBase(), $this->makeExtension());

        $roots = array_map(fn(array $e) => $e['root'], $merged['entries']);
        $this->assertContains('wordthree', $roots);
    }

    #[Test]
    public function mergeDictionariesSkipsDuplicateRootsCaseInsensitive(): void
    {
        $merged = Schema::mergeDictionaries($this->makeBase(), $this->makeExtension());

        $roots = array_map(fn(array $e) => mb_strtolower($e['root']), $merged['entries']);
        $uniqueRoots = array_unique($roots);
        $this->assertCount(count($uniqueRoots), $roots);

        // Only 3 entries: wordone, wordtwo, wordthree (WordOne skipped)
        $this->assertCount(3, $merged['entries']);
    }

    #[Test]
    public function mergeDictionariesUnionsSuffixesDeduplicates(): void
    {
        $merged = Schema::mergeDictionaries($this->makeBase(), $this->makeExtension());

        // ler, lar, dan => 3 unique
        $this->assertCount(3, $merged['suffixes']);
        $this->assertContains('ler', $merged['suffixes']);
        $this->assertContains('lar', $merged['suffixes']);
        $this->assertContains('dan', $merged['suffixes']);
    }

    #[Test]
    public function mergeDictionariesUnionsWhitelistDeduplicates(): void
    {
        $merged = Schema::mergeDictionaries($this->makeBase(), $this->makeExtension());

        // safeone, safetwo, safethree => 3 unique
        $this->assertCount(3, $merged['whitelist']);
        $this->assertContains('safeone', $merged['whitelist']);
        $this->assertContains('safetwo', $merged['whitelist']);
        $this->assertContains('safethree', $merged['whitelist']);
    }

    #[Test]
    public function mergeDictionariesPreservesBaseVersion(): void
    {
        $merged = Schema::mergeDictionaries($this->makeBase(), $this->makeExtension());

        $this->assertSame(1, $merged['version']);
    }

    // ──────────────────────────────────────────────
    //  extendDictionary option (TerlikOptions)
    // ──────────────────────────────────────────────

    private function makeExtDict(): array
    {
        return [
            'version' => 1,
            'suffixes' => ['dan'],
            'entries' => [
                [
                    'root' => 'testbad',
                    'variants' => ['testbadvar'],
                    'severity' => 'high',
                    'category' => 'insult',
                    'suffixable' => true,
                ],
            ],
            'whitelist' => ['testgood'],
        ];
    }

    #[Test]
    public function extendDictionaryDetectsWordsFromExtendedDictionary(): void
    {
        $terlik = new Terlik(new TerlikOptions(extendDictionary: $this->makeExtDict()));

        $this->assertTrue($terlik->containsProfanity('testbad'));
    }

    #[Test]
    public function extendDictionaryStillDetectsBuiltinWords(): void
    {
        $terlik = new Terlik(new TerlikOptions(extendDictionary: $this->makeExtDict()));

        $this->assertTrue($terlik->containsProfanity('siktir'));
    }

    #[Test]
    public function extendDictionaryMergesWhitelistFromExtension(): void
    {
        $terlik = new Terlik(new TerlikOptions(extendDictionary: $this->makeExtDict()));

        // 'testgood' should be whitelisted
        $this->assertFalse($terlik->containsProfanity('testgood'));
    }

    #[Test]
    public function extendDictionaryWorksWithCustomListSimultaneously(): void
    {
        $terlik = new Terlik(new TerlikOptions(
            customList: ['customword'],
            extendDictionary: $this->makeExtDict(),
        ));

        $this->assertTrue($terlik->containsProfanity('customword'));
        $this->assertTrue($terlik->containsProfanity('testbad'));
    }

    #[Test]
    public function extendDictionaryThrowsOnInvalidSchemaVersion(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('version');

        new Terlik(new TerlikOptions(
            extendDictionary: [
                'version' => -1,
                'suffixes' => [],
                'entries' => [],
                'whitelist' => [],
            ],
        ));
    }

    #[Test]
    public function extendDictionaryThrowsOnInvalidEntryEmptyRoot(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('root');

        new Terlik(new TerlikOptions(
            extendDictionary: [
                'version' => 1,
                'suffixes' => [],
                'entries' => [
                    [
                        'root' => '',
                        'variants' => [],
                        'severity' => 'high',
                        'category' => 'insult',
                        'suffixable' => false,
                    ],
                ],
                'whitelist' => [],
            ],
        ));
    }

    #[Test]
    public function extendDictionaryPatternCacheIsolation(): void
    {
        $ext1 = [
            'version' => 1,
            'suffixes' => [],
            'entries' => [
                [
                    'root' => 'extwordone',
                    'variants' => [],
                    'severity' => 'high',
                    'category' => 'insult',
                    'suffixable' => false,
                ],
            ],
            'whitelist' => [],
        ];

        $ext2 = [
            'version' => 1,
            'suffixes' => [],
            'entries' => [
                [
                    'root' => 'extwordtwo',
                    'variants' => [],
                    'severity' => 'high',
                    'category' => 'insult',
                    'suffixable' => false,
                ],
            ],
            'whitelist' => [],
        ];

        $t1 = new Terlik(new TerlikOptions(extendDictionary: $ext1));
        $t2 = new Terlik(new TerlikOptions(extendDictionary: $ext2));

        $this->assertTrue($t1->containsProfanity('extwordone'));
        $this->assertFalse($t1->containsProfanity('extwordtwo'));
        $this->assertFalse($t2->containsProfanity('extwordone'));
        $this->assertTrue($t2->containsProfanity('extwordtwo'));
    }

    #[Test]
    public function extendDictionaryDetectsVariantsFromExtendedDictionary(): void
    {
        $terlik = new Terlik(new TerlikOptions(extendDictionary: $this->makeExtDict()));

        $this->assertTrue($terlik->containsProfanity('testbadvar'));
    }

    #[Test]
    public function extendDictionaryExtendedSuffixesWorkForSuffixableEntries(): void
    {
        $terlik = new Terlik(new TerlikOptions(extendDictionary: $this->makeExtDict()));

        // 'testbad' is suffixable and 'dan' is in extended suffixes
        $this->assertTrue($terlik->containsProfanity('testbaddan'));
    }
}
