<?php

declare(strict_types=1);

namespace Terlik\Tests;

use PHPUnit\Framework\TestCase;
use Terlik\Terlik;
use Terlik\TerlikOptions;
use Terlik\Utils;

/**
 * Edge case tests for profanity detection.
 * Port of edge-cases.test.ts.
 */
final class EdgeCasesTest extends TestCase
{
    private static Terlik $terlik;

    public static function setUpBeforeClass(): void
    {
        self::$terlik = new Terlik();
    }

    // ─── False positives ────────────────────────────────────────

    public function testFalsePositiveSikke(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('sikke'));
    }

    public function testFalsePositiveAmsterdam(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('amsterdam'));
    }

    public function testFalsePositiveAmbulans(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('ambulans'));
    }

    public function testFalsePositiveAmeliyat(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('ameliyat'));
    }

    public function testFalsePositiveMalzeme(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('malzeme'));
    }

    public function testFalsePositiveMemur(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('memur'));
    }

    public function testFalsePositiveBokser(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('bokser'));
    }

    public function testFalsePositiveAma(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('ama'));
    }

    public function testFalsePositiveAmiIsTrue(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('ami'),
            '"ami" is a variant of "am" and should be detected'
        );
    }

    public function testFalsePositiveAmen(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('amen'));
    }

    public function testFalsePositiveAmir(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('amir'));
    }

    public function testFalsePositiveDolmen(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('dolmen'));
    }

    public function testFalsePositiveAmazon(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('amazon'));
    }

    public function testFalsePositiveAmbargo(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('ambargo'));
    }

    public function testFalsePositiveAmblem(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('amblem'));
    }

    public function testFalsePositiveAmfibi(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('amfibi'));
    }

    public function testFalsePositiveDolap(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('dolap'));
    }

    public function testFalsePositiveDolar(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('dolar'));
    }

    public function testFalsePositiveDolma(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('dolma'));
    }

    public function testFalsePositiveDolmus(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('dolmus'));
    }

    public function testFalsePositiveMalum(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('malum'));
    }

    public function testFalsePositiveNamus(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('namus'));
    }

    public function testFalsePositiveNamuslu(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('namuslu'));
    }

    public function testFalsePositiveAhlak(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('ahlak'));
    }

    public function testFalsePositiveAhlaki(): void
    {
        $this->assertFalse(self::$terlik->containsProfanity('ahlaki'));
    }

    // ─── Emoji handling ─────────────────────────────────────────

    public function testEmojiProfanityWithEmojisDetected(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('siktir 😂'),
            'Profanity with emojis should be detected'
        );
    }

    public function testEmojiOnlyIsFalse(): void
    {
        $this->assertFalse(
            self::$terlik->containsProfanity('😀😎🎉'),
            'Emoji-only input should not be detected as profanity'
        );
    }

    // ─── Long input ─────────────────────────────────────────────

    public function testLongInputHandlesUpToMaxLength(): void
    {
        $longCleanText = str_repeat('temiz metin ', 500);
        $this->assertFalse(
            self::$terlik->containsProfanity($longCleanText),
            'Long clean text should not be detected'
        );
    }

    public function testLongInputTruncationBeyondMaxLength(): void
    {
        $maxLength = 50;
        $terlik = new Terlik(new TerlikOptions(maxLength: $maxLength));

        // Profanity placed beyond maxLength should be truncated and not detected
        $text = str_repeat('a', 60) . ' siktir';
        $this->assertFalse(
            $terlik->containsProfanity($text),
            'Profanity beyond maxLength should be truncated and not detected'
        );
    }

    // ─── Empty and special input ────────────────────────────────

    public function testEmptyString(): void
    {
        $this->assertFalse(
            self::$terlik->containsProfanity(''),
            'Empty string should not be detected'
        );
    }

    public function testWhitespaceOnly(): void
    {
        $this->assertFalse(
            self::$terlik->containsProfanity('   '),
            'Whitespace-only should not be detected'
        );
    }

    public function testNumbersOnly(): void
    {
        $this->assertFalse(
            self::$terlik->containsProfanity('123456'),
            'Numbers-only should not be detected'
        );
    }

    public function testSpecialCharsOnly(): void
    {
        $this->assertFalse(
            self::$terlik->containsProfanity('!@#$%^&*()'),
            'Special chars only should not be detected'
        );
    }

    // ─── Turkish İ/ı variations ─────────────────────────────────

    public function testTurkishUpperI(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('SİKTİR'),
            'Turkish İ uppercase should be detected'
        );
    }

    public function testTurkishLowerI(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('siktir'),
            'Turkish lowercase i in siktir should be detected'
        );
    }

    // ─── Leet speak ─────────────────────────────────────────────

    public function testLeetSiktir(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('$1kt1r'),
            'Leet $1kt1r should be detected'
        );
    }

    public function testLeetAptal(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('@pt@l'),
            'Leet @pt@l should be detected'
        );
    }

    public function testLeetBok(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('8ok'),
            'Leet 8ok should be detected'
        );
    }

    public function testLeetGot(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('6öt'),
            'Leet 6öt should be detected'
        );
    }

    public function testLeetS2kt2r(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('s2kt2r'),
            'Leet s2kt2r should be detected'
        );
    }

    // ─── Repetition ─────────────────────────────────────────────

    public function testRepetitionSiiiiiktir(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('siiiiiktir'),
            'Repeated characters siiiiiktir should be detected'
        );
    }

    public function testRepetitionOrrrospu(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('orrrospu'),
            'Repeated characters orrrospu should be detected'
        );
    }

    // ─── Separators ─────────────────────────────────────────────

    public function testSeparatorDots(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('s.i.k.t.i.r'),
            'Dot-separated s.i.k.t.i.r should be detected'
        );
    }

    public function testSeparatorDashes(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('s-i-k-t-i-r'),
            'Dash-separated s-i-k-t-i-r should be detected'
        );
    }

    public function testSeparatorUnderscores(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('s_i_k_t_i_r'),
            'Underscore-separated s_i_k_t_i_r should be detected'
        );
    }

    // ─── New variants ───────────────────────────────────────────

    public function testVariantAminakoyayim(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('aminakoyayim'),
            '"aminakoyayim" should be detected'
        );
    }

    public function testVariantAminakoydum(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('aminakoydum'),
            '"aminakoydum" should be detected'
        );
    }

    public function testVariantAminakoydugumun(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('aminakoydugumun'),
            '"aminakoydugumun" should be detected'
        );
    }

    public function testVariantAq(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('aq'),
            '"aq" should be detected'
        );
    }

    public function testVariantOrospucocuklari(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('orospucocuklari'),
            '"orospucocuklari" should be detected'
        );
    }

    public function testVariantGotos(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('gotos'),
            '"gotos" should be detected'
        );
    }

    public function testVariantYarrani(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('yarrani'),
            '"yarrani" should be detected'
        );
    }

    public function testVariantYarragimi(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('yarragimi'),
            '"yarragimi" should be detected'
        );
    }

    public function testVariantYarragini(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('yarragini'),
            '"yarragini" should be detected'
        );
    }

    public function testVariantSktr(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('sktr'),
            '"sktr" should be detected'
        );
    }

    // ─── Turkish numbers ────────────────────────────────────────

    public function testTurkishNumberS2k(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('s2k'),
            '"s2k" should be detected (2→i in leet)'
        );
    }

    public function testTurkishNumberS2mle(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('s2mle'),
            '"s2mle" should be detected'
        );
    }

    public function testStandaloneNumbersSafe(): void
    {
        $this->assertFalse(
            self::$terlik->containsProfanity('100'),
            'Standalone numbers should be safe'
        );
    }

    public function testNumberInSentenceSafe(): void
    {
        $this->assertFalse(
            self::$terlik->containsProfanity('100 kisi'),
            '"100 kisi" should be safe'
        );
    }
}
