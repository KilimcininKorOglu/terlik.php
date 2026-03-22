<?php

declare(strict_types=1);

namespace Terlik\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Terlik\NormalizerConfig;
use Terlik\TextNormalizer;

final class NormalizerTest extends TestCase
{
    private TextNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new TextNormalizer(new NormalizerConfig(
            locale: 'tr',
            charMap: [
                'ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g',
                'ı' => 'i', 'İ' => 'i', 'ö' => 'o', 'Ö' => 'o',
                'ş' => 's', 'Ş' => 's', 'ü' => 'u', 'Ü' => 'u',
            ],
            leetMap: [
                '0' => 'o', '1' => 'i', '2' => 'i', '3' => 'e',
                '4' => 'a', '5' => 's', '6' => 'g', '7' => 't',
                '8' => 'b', '9' => 'g', '@' => 'a', '$' => 's',
                '!' => 'i',
            ],
            numberExpansions: [
                ['100', 'yuz'],
                ['50', 'elli'],
                ['10', 'on'],
                ['2', 'iki'],
            ],
        ));
    }

    // ─── toLowercase with Turkish locale ─────────────────────

    #[Test]
    public function testTurkishLocaleConvertsIstanbulToLowercase(): void
    {
        $result = $this->normalizer->normalize('İSTANBUL');
        $this->assertSame('istanbul', $result);
    }

    // ─── replaceTurkishChars via replaceFromMap ──────────────

    #[Test]
    public function testReplaceTurkishCharsFoldsDiacritics(): void
    {
        $charMap = [
            'ç' => 'c', 'ğ' => 'g', 'ı' => 'i',
            'ö' => 'o', 'ş' => 's', 'ü' => 'u',
        ];
        $result = TextNormalizer::replaceFromMap('çğıöşü', $charMap);
        $this->assertSame('cgiosu', $result);
    }

    // ─── replaceLeetspeak via replaceFromMap ─────────────────

    #[Test]
    public function testReplaceLeetSpeakH3ll0ToHello(): void
    {
        $leetMap = [
            '3' => 'e', '0' => 'o',
        ];
        $result = TextNormalizer::replaceFromMap('h3ll0', $leetMap);
        $this->assertSame('hello', $result);
    }

    #[Test]
    public function testReplaceLeetSpeakDollar1kToSik(): void
    {
        $leetMap = [
            '$' => 's', '1' => 'i',
        ];
        $result = TextNormalizer::replaceFromMap('$1k', $leetMap);
        $this->assertSame('sik', $result);
    }

    // ─── removePunctuation ───────────────────────────────────

    #[Test]
    public function testRemovePunctuationBetweenLetters(): void
    {
        $result = TextNormalizer::removePunctuation('s.i.k');
        $this->assertSame('sik', $result);
    }

    #[Test]
    public function testRemovePunctuationPreservesBoundary(): void
    {
        // Punctuation at boundaries (start/end) is preserved
        $result = TextNormalizer::removePunctuation('.sik.');
        $this->assertSame('.sik.', $result);
    }

    // ─── collapseRepeats ─────────────────────────────────────

    #[Test]
    public function testCollapseRepeatsReducesTripleToSingle(): void
    {
        $result = TextNormalizer::collapseRepeats('siiik');
        $this->assertSame('sik', $result);
    }

    #[Test]
    public function testCollapseRepeatsPreservesDoubleLetters(): void
    {
        $result = TextNormalizer::collapseRepeats('oo');
        $this->assertSame('oo', $result);
    }

    // ─── trimWhitespace ──────────────────────────────────────

    #[Test]
    public function testTrimWhitespaceCollapsesMultipleSpaces(): void
    {
        $result = TextNormalizer::trimWhitespace('  hello    world  ');
        $this->assertSame('hello world', $result);
    }

    // ─── Full pipeline tests ─────────────────────────────────

    #[Test]
    public function testFullPipelineDottedSeparatorsWithTurkishChars(): void
    {
        $result = $this->normalizer->normalize('S.İ.K.T.İ.R');
        $this->assertSame('siktir', $result);
    }

    #[Test]
    public function testFullPipelineLeetSpeakWithPunctuation(): void
    {
        $result = $this->normalizer->normalize('$1k7!r');
        $this->assertSame('siktir', $result);
    }

    #[Test]
    public function testFullPipelineRepeatedCharactersWithUppercase(): void
    {
        $result = $this->normalizer->normalize('SIIIKTIR');
        $this->assertSame('siktir', $result);
    }

    #[Test]
    public function testFullPipelineEmptyString(): void
    {
        $result = $this->normalizer->normalize('');
        $this->assertSame('', $result);
    }

    #[Test]
    public function testFullPipelinePreservesEmojiInOutput(): void
    {
        // Emojis should pass through the pipeline without being stripped
        $result = $this->normalizer->normalize('hello 😀');
        $this->assertStringContainsString('😀', $result);
    }

    // ─── createNormalizer factory ────────────────────────────

    #[Test]
    public function testCreateNormalizerReturnsCallable(): void
    {
        $normalize = TextNormalizer::createNormalizer(new NormalizerConfig(
            locale: 'tr',
            charMap: ['ş' => 's'],
            leetMap: [],
        ));

        $this->assertIsCallable($normalize);
        $this->assertSame('selam', $normalize('şelam'));
    }

    // ─── normalizeTurkish shorthand ──────────────────────────

    #[Test]
    public function testNormalizeTurkishShorthand(): void
    {
        $result = TextNormalizer::normalizeTurkish('İSTANBUL');
        $this->assertSame('istanbul', $result);
    }
}
