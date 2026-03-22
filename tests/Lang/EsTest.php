<?php

declare(strict_types=1);

namespace Terlik\Tests\Lang;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Terlik\Terlik;
use Terlik\TerlikOptions;

final class EsTest extends TestCase
{
    private static ?Terlik $terlik = null;

    private static function terlik(): Terlik
    {
        return self::$terlik ??= new Terlik(new TerlikOptions(language: 'es'));
    }

    // ──────────────────────────────────────────────────────────
    //  Root detection (28 roots)
    // ──────────────────────────────────────────────────────────

    /** @return array<string, array{string}> */
    public static function rootProvider(): array
    {
        return [
            'mierda'       => ['mierda'],
            'puta'         => ['puta'],
            'cabron'       => ['cabron'],
            'joder'        => ['joder'],
            'coño'         => ['coño'],
            'verga'        => ['verga'],
            'chingar'      => ['chingar'],
            'pendejo'      => ['pendejo'],
            'marica'       => ['marica'],
            'carajo'       => ['carajo'],
            'idiota'       => ['idiota'],
            'culo'         => ['culo'],
            'zorra'        => ['zorra'],
            'estupido'     => ['estupido'],
            'imbecil'      => ['imbecil'],
            'gilipollas'   => ['gilipollas'],
            'huevon'       => ['huevon'],
            'pinche'       => ['pinche'],
            'culero'       => ['culero'],
            'cojones'      => ['cojones'],
            'polla'        => ['polla'],
            'follar'       => ['follar'],
            'capullo'      => ['capullo'],
            'guarro'       => ['guarro'],
            'boludo'       => ['boludo'],
            'pelotudo'     => ['pelotudo'],
            'hostia'       => ['hostia'],
            'soplapollas'  => ['soplapollas'],
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
    //  Variant detection (46 variants)
    // ──────────────────────────────────────────────────────────

    /** @return array<string, array{string}> */
    public static function variantProvider(): array
    {
        return [
            'puto'        => ['puto'],
            'putas'       => ['putas'],
            'hijoputa'    => ['hijoputa'],
            'putear'      => ['putear'],
            'putazo'      => ['putazo'],
            'puteada'     => ['puteada'],
            'jodido'      => ['jodido'],
            'jodida'      => ['jodida'],
            'jodiendo'    => ['jodiendo'],
            'chingado'    => ['chingado'],
            'chingada'    => ['chingada'],
            'chingon'     => ['chingon'],
            'chingona'    => ['chingona'],
            'chingadera'  => ['chingadera'],
            'chingue'     => ['chingue'],
            'pendejos'    => ['pendejos'],
            'pendeja'     => ['pendeja'],
            'pendejada'   => ['pendejada'],
            'maricon'     => ['maricon'],
            'maricones'   => ['maricones'],
            'cabrones'    => ['cabrones'],
            'cabrona'     => ['cabrona'],
            'cabronazo'   => ['cabronazo'],
            'mierdoso'    => ['mierdoso'],
            'estupida'    => ['estupida'],
            'estupidez'   => ['estupidez'],
            'coñazo'      => ['coñazo'],
            'culeros'     => ['culeros'],
            'culera'      => ['culera'],
            'cojonudo'    => ['cojonudo'],
            'pollas'      => ['pollas'],
            'pollon'      => ['pollon'],
            'follando'    => ['follando'],
            'follado'     => ['follado'],
            'follada'     => ['follada'],
            'capullos'    => ['capullos'],
            'guarros'     => ['guarros'],
            'guarra'      => ['guarra'],
            'guarrada'    => ['guarrada'],
            'boludos'     => ['boludos'],
            'boluda'      => ['boluda'],
            'boludez'     => ['boludez'],
            'pelotudos'   => ['pelotudos'],
            'pelotuda'    => ['pelotuda'],
            'pelotudez'   => ['pelotudez'],
            'mamonazo'    => ['mamonazo'],
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
    //  Evasion detection
    // ──────────────────────────────────────────────────────────

    /** @return array<string, array{string}> */
    public static function evasionProvider(): array
    {
        return [
            'separator: m.i.e.r.d.a' => ['m.i.e.r.d.a'],
            'leet: m1erda'            => ['m1erda'],
            'separator: p.u.t.a'     => ['p.u.t.a'],
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
            'computadora'  => ['computadora'],
            'disputar'     => ['disputar'],
            'reputacion'   => ['reputacion'],
            'calcular'     => ['calcular'],
            'particular'   => ['particular'],
            'vehicular'    => ['vehicular'],
            'pollo'        => ['pollo'],
            'pollito'      => ['pollito'],
            'polleria'     => ['polleria'],
            'polluelo'     => ['polluelo'],
            'folleto'      => ['folleto'],
            'follaje'      => ['follaje'],
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
}
