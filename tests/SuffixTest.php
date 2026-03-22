<?php

declare(strict_types=1);

namespace Terlik\Tests;

use PHPUnit\Framework\TestCase;
use Terlik\Terlik;

/**
 * Suffix detection tests for Turkish profanity roots.
 * Port of suffix.test.ts.
 */
final class SuffixTest extends TestCase
{
    private static Terlik $terlik;

    public static function setUpBeforeClass(): void
    {
        self::$terlik = new Terlik();
    }

    // ─── Suffixable roots ───────────────────────────────────────

    public function testSuffixSiktiler(): void
    {
        $this->assertTrue(self::$terlik->containsProfanity('siktiler'));
    }

    public function testSuffixSikerim(): void
    {
        $this->assertTrue(self::$terlik->containsProfanity('sikerim'));
    }

    public function testSuffixOrospuluk(): void
    {
        $this->assertTrue(self::$terlik->containsProfanity('orospuluk'));
    }

    public function testSuffixGotune(): void
    {
        $this->assertTrue(self::$terlik->containsProfanity('gotune'));
    }

    public function testSuffixBoktan(): void
    {
        $this->assertTrue(self::$terlik->containsProfanity('boktan'));
    }

    public function testSuffixIbnelik(): void
    {
        $this->assertTrue(self::$terlik->containsProfanity('ibnelik'));
    }

    public function testSuffixGavatlar(): void
    {
        $this->assertTrue(self::$terlik->containsProfanity('gavatlar'));
    }

    public function testSuffixSalaksin(): void
    {
        $this->assertTrue(self::$terlik->containsProfanity('salaksin'));
    }

    public function testSuffixAptallarin(): void
    {
        $this->assertTrue(self::$terlik->containsProfanity('aptallarin'));
    }

    public function testSuffixKahpeler(): void
    {
        $this->assertTrue(self::$terlik->containsProfanity('kahpeler'));
    }

    public function testSuffixPezevenkler(): void
    {
        $this->assertTrue(self::$terlik->containsProfanity('pezevenkler'));
    }

    public function testSuffixYavsaklik(): void
    {
        $this->assertTrue(self::$terlik->containsProfanity('yavsaklik'));
    }

    public function testSuffixSerefsizler(): void
    {
        $this->assertTrue(self::$terlik->containsProfanity('serefsizler'));
    }

    public function testSuffixPustlar(): void
    {
        $this->assertTrue(self::$terlik->containsProfanity('pustlar'));
    }

    // ─── Suffix chaining ────────────────────────────────────────

    public function testSuffixChainingSiktirler(): void
    {
        $this->assertTrue(self::$terlik->containsProfanity('siktirler'));
    }

    public function testSuffixChainingOrospuluklar(): void
    {
        $this->assertTrue(self::$terlik->containsProfanity('orospuluklar'));
    }

    // ─── Evasion + suffix ───────────────────────────────────────

    public function testEvasionSuffixSeparator(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('s.i.k.t.i.r.l.e.r'),
            'Separator evasion with suffix should be detected'
        );
    }

    public function testEvasionSuffixLeet(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('$1kt1rler'),
            'Leet evasion with suffix should be detected'
        );
    }

    // ─── Non-suffixable ─────────────────────────────────────────

    public function testNonSuffixableAmaNeden(): void
    {
        $this->assertFalse(
            self::$terlik->containsProfanity('ama neden'),
            '"ama neden" should not be detected as profanity'
        );
    }

    public function testNonSuffixableAmiBozuk(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('ami bozuk'),
            '"ami bozuk" should be detected as profanity (ami is a variant of am)'
        );
    }

    // ─── False positive prevention ──────────────────────────────

    public function testFalsePositiveAma(): void
    {
        $this->assertFalse(
            self::$terlik->containsProfanity('ama'),
            '"ama" should not be detected (whitelisted)'
        );
    }

    public function testFalsePositiveAmi(): void
    {
        $this->assertTrue(
            self::$terlik->containsProfanity('ami'),
            '"ami" should be detected (variant of am)'
        );
    }

    public function testFalsePositiveAmen(): void
    {
        $this->assertFalse(
            self::$terlik->containsProfanity('amen'),
            '"amen" should not be detected (whitelisted)'
        );
    }

    public function testFalsePositiveSikke(): void
    {
        $this->assertFalse(
            self::$terlik->containsProfanity('sikke'),
            '"sikke" should not be detected (whitelisted)'
        );
    }

    public function testFalsePositiveAmsterdam(): void
    {
        $this->assertFalse(
            self::$terlik->containsProfanity('amsterdam'),
            '"amsterdam" should not be detected (whitelisted)'
        );
    }

    public function testFalsePositiveBokser(): void
    {
        $this->assertFalse(
            self::$terlik->containsProfanity('bokser'),
            '"bokser" should not be detected (whitelisted)'
        );
    }

    public function testFalsePositiveDolmen(): void
    {
        $this->assertFalse(
            self::$terlik->containsProfanity('dolmen'),
            '"dolmen" should not be detected (whitelisted)'
        );
    }

    public function testFalsePositiveDolunay(): void
    {
        $this->assertFalse(
            self::$terlik->containsProfanity('dolunay'),
            '"dolunay" should not be detected (whitelisted)'
        );
    }

    public function testFalsePositiveSikma(): void
    {
        $this->assertFalse(
            self::$terlik->containsProfanity('sıkma'),
            '"sıkma" should not be detected (whitelisted)'
        );
    }

    public function testFalsePositiveSikinti(): void
    {
        $this->assertFalse(
            self::$terlik->containsProfanity('sıkıntı'),
            '"sıkıntı" should not be detected (whitelisted)'
        );
    }

    public function testFalsePositiveSikisti(): void
    {
        $this->assertFalse(
            self::$terlik->containsProfanity('sıkıştı'),
            '"sıkıştı" should not be detected (whitelisted)'
        );
    }

    public function testFalsePositiveSiki(): void
    {
        $this->assertFalse(
            self::$terlik->containsProfanity('sıkı'),
            '"sıkı" should not be detected (whitelisted)'
        );
    }

    public function testFalsePositiveAmir(): void
    {
        $this->assertFalse(
            self::$terlik->containsProfanity('amir'),
            '"amir" should not be detected (whitelisted)'
        );
    }
}
