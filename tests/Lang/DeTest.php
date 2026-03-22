<?php

declare(strict_types=1);

namespace Terlik\Tests\Lang;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Terlik\Terlik;
use Terlik\TerlikOptions;

final class DeTest extends TestCase
{
    private static ?Terlik $terlik = null;

    private static function terlik(): Terlik
    {
        return self::$terlik ??= new Terlik(new TerlikOptions(language: 'de'));
    }

    // ──────────────────────────────────────────────────────────
    //  Root detection (28 roots)
    // ──────────────────────────────────────────────────────────

    /** @return array<string, array{string}> */
    public static function rootProvider(): array
    {
        return [
            'scheiße'      => ['scheiße'],
            'fick'         => ['fick'],
            'arsch'        => ['arsch'],
            'hurensohn'    => ['hurensohn'],
            'hure'         => ['hure'],
            'fotze'        => ['fotze'],
            'wichser'      => ['wichser'],
            'schwanz'      => ['schwanz'],
            'schlampe'     => ['schlampe'],
            'mistkerl'     => ['mistkerl'],
            'idiot'        => ['idiot'],
            'dumm'         => ['dumm'],
            'depp'         => ['depp'],
            'vollidiot'    => ['vollidiot'],
            'missgeburt'   => ['missgeburt'],
            'drecksau'     => ['drecksau'],
            'dreck'        => ['dreck'],
            'trottel'      => ['trottel'],
            'schwuchtel'   => ['schwuchtel'],
            'spast'        => ['spast'],
            'miststück'    => ['miststück'],
            'bastard'      => ['bastard'],
            'penner'       => ['penner'],
            'blödmann'     => ['blödmann'],
            'vollpfosten'  => ['vollpfosten'],
            'hackfresse'   => ['hackfresse'],
            'pissnelke'    => ['pissnelke'],
            'spacken'      => ['spacken'],
        ];
    }

    #[Test]
    #[DataProvider('rootProvider')]
    public function rootDetection(string $word): void
    {
        $this->assertTrue(
            self::terlik()->containsProfanity($word),
            "Root '{$word}' should be detected as profanity"
        );
    }

    // ──────────────────────────────────────────────────────────
    //  Variant detection (32 variants)
    // ──────────────────────────────────────────────────────────

    /** @return array<string, array{string}> */
    public static function variantProvider(): array
    {
        return [
            'scheisse'      => ['scheisse'],
            'scheiss'       => ['scheiss'],
            'beschissen'    => ['beschissen'],
            'scheissegal'   => ['scheissegal'],
            'ficken'        => ['ficken'],
            'ficker'        => ['ficker'],
            'gefickt'       => ['gefickt'],
            'verfickt'      => ['verfickt'],
            'fickfehler'    => ['fickfehler'],
            'arschloch'     => ['arschloch'],
            'arschgeige'    => ['arschgeige'],
            'arschgesicht'  => ['arschgesicht'],
            'arschbacke'    => ['arschbacke'],
            'arschlocher'   => ['arschlocher'],
            'fotzen'        => ['fotzen'],
            'wichsen'       => ['wichsen'],
            'gewichst'      => ['gewichst'],
            'wixer'         => ['wixer'],
            'schlampig'     => ['schlampig'],
            'schlamperei'   => ['schlamperei'],
            'dummkopf'      => ['dummkopf'],
            'dummheit'      => ['dummheit'],
            'dreckig'       => ['dreckig'],
            'drecksack'     => ['drecksack'],
            'vollidioten'   => ['vollidioten'],
            'missgeburten'  => ['missgeburten'],
            'schwuchteln'   => ['schwuchteln'],
            'spasten'       => ['spasten'],
            'spasti'        => ['spasti'],
            'miststueck'    => ['miststueck'],
            'bastarde'      => ['bastarde'],
            'blodmann'      => ['blodmann'],
        ];
    }

    #[Test]
    #[DataProvider('variantProvider')]
    public function variantDetection(string $word): void
    {
        $this->assertTrue(
            self::terlik()->containsProfanity($word),
            "Variant '{$word}' should be detected as profanity"
        );
    }

    // ──────────────────────────────────────────────────────────
    //  ß handling — scheiße / scheisse / SCHEISSE
    // ──────────────────────────────────────────────────────────

    #[Test]
    public function detectsScheisseWithEszett(): void
    {
        $this->assertTrue(self::terlik()->containsProfanity('scheiße'));
    }

    #[Test]
    public function detectsScheisseWithDoubleSs(): void
    {
        $this->assertTrue(self::terlik()->containsProfanity('scheisse'));
    }

    #[Test]
    public function detectsScheisseUppercase(): void
    {
        $this->assertTrue(self::terlik()->containsProfanity('SCHEISSE'));
    }

    // ──────────────────────────────────────────────────────────
    //  Evasion detection
    // ──────────────────────────────────────────────────────────

    /** @return array<string, array{string}> */
    public static function evasionProvider(): array
    {
        return [
            'separator: f.i.c.k'         => ['f.i.c.k'],
            'separator: s.c.h.e.i.s.s.e' => ['s.c.h.e.i.s.s.e'],
        ];
    }

    #[Test]
    #[DataProvider('evasionProvider')]
    public function evasionDetection(string $word): void
    {
        $this->assertTrue(
            self::terlik()->containsProfanity($word),
            "Evasion '{$word}' should be detected as profanity"
        );
    }

    // ──────────────────────────────────────────────────────────
    //  Whitelist — safe words that must NOT trigger detection
    // ──────────────────────────────────────────────────────────

    /** @return array<string, array{string}> */
    public static function whitelistProvider(): array
    {
        return [
            'schwanger'        => ['schwanger'],
            'schwangerschaft'  => ['schwangerschaft'],
            'geschichte'       => ['geschichte'],
        ];
    }

    #[Test]
    #[DataProvider('whitelistProvider')]
    public function whitelistSafeWords(string $word): void
    {
        $this->assertFalse(
            self::terlik()->containsProfanity($word),
            "Safe word '{$word}' must NOT be detected as profanity"
        );
    }

    // ──────────────────────────────────────────────────────────
    //  Isolation — must NOT detect other languages' profanity
    // ──────────────────────────────────────────────────────────

    #[Test]
    public function doesNotDetectTurkishProfanity(): void
    {
        $this->assertFalse(self::terlik()->containsProfanity('siktir'));
    }

    #[Test]
    public function doesNotDetectEnglishProfanity(): void
    {
        $this->assertFalse(self::terlik()->containsProfanity('fuck'));
    }

    #[Test]
    public function doesNotDetectSpanishProfanity(): void
    {
        $this->assertFalse(self::terlik()->containsProfanity('mierda'));
    }
}
