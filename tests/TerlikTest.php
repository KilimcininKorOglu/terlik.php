<?php

declare(strict_types=1);

namespace Terlik\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Terlik\CleanOptions;
use Terlik\DetectOptions;
use Terlik\MaskStyle;
use Terlik\MatchMethod;
use Terlik\Mode;
use Terlik\Severity;
use Terlik\Terlik;
use Terlik\TerlikOptions;

final class TerlikTest extends TestCase
{
    // ─── containsProfanity ───────────────────────────────────

    #[Test]
    public function testContainsProfanityReturnsTrueForProfaneText(): void
    {
        $terlik = new Terlik();
        $this->assertTrue($terlik->containsProfanity('siktir git'));
    }

    #[Test]
    public function testContainsProfanityReturnsFalseForCleanText(): void
    {
        $terlik = new Terlik();
        $this->assertFalse($terlik->containsProfanity('merhaba dunya'));
    }

    #[Test]
    public function testContainsProfanityReturnsFalseForEmptyString(): void
    {
        $terlik = new Terlik();
        $this->assertFalse($terlik->containsProfanity(''));
    }

    // ─── getMatches ──────────────────────────────────────────

    #[Test]
    public function testGetMatchesReturnsMatchDetailsForProfanity(): void
    {
        $terlik = new Terlik();
        $matches = $terlik->getMatches('siktir git');

        $this->assertNotEmpty($matches);

        $first = $matches[0];
        $this->assertNotEmpty($first->word);
        $this->assertNotEmpty($first->root);
        $this->assertIsInt($first->index);
        $this->assertInstanceOf(Severity::class, $first->severity);
        $this->assertInstanceOf(MatchMethod::class, $first->method);
    }

    #[Test]
    public function testGetMatchesReturnsEmptyArrayForCleanText(): void
    {
        $terlik = new Terlik();
        $matches = $terlik->getMatches('merhaba dunya');

        $this->assertEmpty($matches);
    }

    // ─── clean ───────────────────────────────────────────────

    #[Test]
    public function testCleanUsesStarsMaskByDefault(): void
    {
        $terlik = new Terlik();
        $result = $terlik->clean('siktir git');

        $this->assertStringNotContainsString('siktir', $result);
        $this->assertStringContainsString('*', $result);
    }

    #[Test]
    public function testCleanWithPartialMask(): void
    {
        $terlik = new Terlik(new TerlikOptions(maskStyle: MaskStyle::Partial));
        $result = $terlik->clean('siktir git');

        $this->assertStringNotContainsString('siktir', $result);
        // Partial mask keeps first and last character
        $this->assertMatchesRegularExpression('/\w\*+\w/', $result);
    }

    #[Test]
    public function testCleanWithReplaceMask(): void
    {
        $terlik = new Terlik(new TerlikOptions(
            maskStyle: MaskStyle::Replace,
            replaceMask: '[küfür]',
        ));
        $result = $terlik->clean('siktir git');

        $this->assertStringContainsString('[küfür]', $result);
    }

    #[Test]
    public function testCleanLeavesCleanTextUnchanged(): void
    {
        $terlik = new Terlik();
        $text = 'merhaba dunya';
        $result = $terlik->clean($text);

        $this->assertSame($text, $result);
    }

    // ─── addWords / removeWords ──────────────────────────────

    #[Test]
    public function testAddWordsDetectsNewlyAddedWord(): void
    {
        $terlik = new Terlik();
        $this->assertFalse($terlik->containsProfanity('kodumun'));

        $terlik->addWords(['kodumun']);
        $this->assertTrue($terlik->containsProfanity('kodumun'));
    }

    #[Test]
    public function testRemoveWordsStopsDetectingRemovedWord(): void
    {
        $terlik = new Terlik();
        $this->assertTrue($terlik->containsProfanity('salak'));

        $terlik->removeWords(['salak']);
        $this->assertFalse($terlik->containsProfanity('salak'));
    }

    // ─── modes ───────────────────────────────────────────────

    #[Test]
    public function testStrictModeDoesNotCatchSpacedLetters(): void
    {
        $terlik = new Terlik(new TerlikOptions(mode: Mode::Strict));
        $this->assertFalse($terlik->containsProfanity('s i k t i r'));
    }

    #[Test]
    public function testBalancedModeCatchesDottedSeparators(): void
    {
        $terlik = new Terlik(new TerlikOptions(mode: Mode::Balanced));
        $this->assertTrue($terlik->containsProfanity('s.i.k.t.i.r'));
    }

    #[Test]
    public function testLooseModeCatchesRepeatedCharacters(): void
    {
        $terlik = new Terlik(new TerlikOptions(mode: Mode::Loose));
        $this->assertTrue($terlik->containsProfanity('siktiir'));
    }

    // ─── custom options ──────────────────────────────────────

    #[Test]
    public function testWhitelistExcludesWordsFromDetection(): void
    {
        $terlik = new Terlik(new TerlikOptions(whitelist: ['sikke']));
        $this->assertFalse($terlik->containsProfanity('sikke'));
    }

    #[Test]
    public function testCustomListDetectsAdditionalWords(): void
    {
        $terlik = new Terlik(new TerlikOptions(customList: ['hiyar']));
        $this->assertTrue($terlik->containsProfanity('hiyar'));
    }

    #[Test]
    public function testMaxLengthTruncatesInput(): void
    {
        $terlik = new Terlik(new TerlikOptions(maxLength: 5));
        // "siktir" is 6 chars, truncated to 5 => "sikti" which should still match root "sik"
        $result = $terlik->containsProfanity('siktir');
        // The truncated text may or may not match depending on pattern.
        // The key behavior is no error and the text is truncated.
        $cleaned = $terlik->clean('siktir');
        $this->assertLessThanOrEqual(5, mb_strlen($cleaned));
    }

    // ─── getPatterns ─────────────────────────────────────────

    #[Test]
    public function testGetPatternsReturnsNonEmptyArray(): void
    {
        $terlik = new Terlik();
        $patterns = $terlik->getPatterns();

        $this->assertIsArray($patterns);
        $this->assertNotEmpty($patterns);
    }
}
