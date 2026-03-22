<?php

declare(strict_types=1);

namespace Terlik\Tests\Lang;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Terlik\Terlik;
use Terlik\TerlikOptions;
use Terlik\Mode;

final class MultiLangTest extends TestCase
{
    // ──────────────────────────────────────────────
    //  Turkish detects TR, not EN/ES/DE
    // ──────────────────────────────────────────────

    #[Test]
    public function turkishDetectsTurkishProfanity(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'tr'));

        $this->assertTrue($terlik->containsProfanity('siktir'));
    }

    #[Test]
    public function turkishDoesNotDetectEnglish(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'tr'));

        $this->assertFalse($terlik->containsProfanity('fuck'));
    }

    #[Test]
    public function turkishDoesNotDetectSpanish(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'tr'));

        $this->assertFalse($terlik->containsProfanity('mierda'));
    }

    #[Test]
    public function turkishDoesNotDetectGerman(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'tr'));

        $this->assertFalse($terlik->containsProfanity("\u{00DF}chei\u{00DF}e")); // scheiße with raw chars
        // More readable alternative
        $this->assertFalse($terlik->containsProfanity('scheiße'));
    }

    // ──────────────────────────────────────────────
    //  English detects EN, not TR/ES/DE
    // ──────────────────────────────────────────────

    #[Test]
    public function englishDetectsEnglishProfanity(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'en'));

        $this->assertTrue($terlik->containsProfanity('fuck'));
    }

    #[Test]
    public function englishDoesNotDetectTurkish(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'en'));

        $this->assertFalse($terlik->containsProfanity('siktir'));
    }

    #[Test]
    public function englishDoesNotDetectSpanish(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'en'));

        $this->assertFalse($terlik->containsProfanity('mierda'));
    }

    #[Test]
    public function englishDoesNotDetectGerman(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'en'));

        $this->assertFalse($terlik->containsProfanity('scheiße'));
    }

    // ──────────────────────────────────────────────
    //  Spanish detects ES, not TR/EN/DE
    // ──────────────────────────────────────────────

    #[Test]
    public function spanishDetectsSpanishProfanity(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'es'));

        $this->assertTrue($terlik->containsProfanity('mierda'));
    }

    #[Test]
    public function spanishDoesNotDetectTurkish(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'es'));

        $this->assertFalse($terlik->containsProfanity('siktir'));
    }

    #[Test]
    public function spanishDoesNotDetectEnglish(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'es'));

        $this->assertFalse($terlik->containsProfanity('fuck'));
    }

    #[Test]
    public function spanishDoesNotDetectGerman(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'es'));

        $this->assertFalse($terlik->containsProfanity('scheiße'));
    }

    // ──────────────────────────────────────────────
    //  German detects DE, not TR/EN/ES
    // ──────────────────────────────────────────────

    #[Test]
    public function germanDetectsGermanProfanity(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'de'));

        $this->assertTrue($terlik->containsProfanity('scheiße'));
    }

    #[Test]
    public function germanDoesNotDetectTurkish(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'de'));

        $this->assertFalse($terlik->containsProfanity('siktir'));
    }

    #[Test]
    public function germanDoesNotDetectEnglish(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'de'));

        $this->assertFalse($terlik->containsProfanity('fuck'));
    }

    #[Test]
    public function germanDoesNotDetectSpanish(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'de'));

        $this->assertFalse($terlik->containsProfanity('mierda'));
    }

    // ──────────────────────────────────────────────
    //  addWords is instance-scoped
    // ──────────────────────────────────────────────

    #[Test]
    public function addWordsIsInstanceScoped(): void
    {
        $tr = new Terlik(new TerlikOptions(language: 'tr'));
        $en = new Terlik(new TerlikOptions(language: 'en'));

        $tr->addWords(['kustom']);

        $this->assertTrue($tr->containsProfanity('kustom'));
        $this->assertFalse($en->containsProfanity('kustom'));
    }

    // ──────────────────────────────────────────────
    //  language property readable
    // ──────────────────────────────────────────────

    #[Test]
    public function languagePropertyReadable(): void
    {
        $tr = new Terlik(new TerlikOptions(language: 'tr'));
        $en = new Terlik(new TerlikOptions(language: 'en'));
        $es = new Terlik(new TerlikOptions(language: 'es'));
        $de = new Terlik(new TerlikOptions(language: 'de'));

        $this->assertSame('tr', $tr->language);
        $this->assertSame('en', $en->language);
        $this->assertSame('es', $es->language);
        $this->assertSame('de', $de->language);
    }

    // ──────────────────────────────────────────────
    //  Default language is Turkish
    // ──────────────────────────────────────────────

    #[Test]
    public function defaultLanguageIsTurkish(): void
    {
        $terlik = new Terlik();

        $this->assertSame('tr', $terlik->language);
    }

    // ──────────────────────────────────────────────
    //  Terlik::warmup
    // ──────────────────────────────────────────────

    #[Test]
    public function warmupCreatesInstancesForAllLanguages(): void
    {
        $instances = Terlik::warmup();

        $this->assertCount(4, $instances);
        $this->assertArrayHasKey('tr', $instances);
        $this->assertArrayHasKey('en', $instances);
        $this->assertArrayHasKey('es', $instances);
        $this->assertArrayHasKey('de', $instances);

        foreach ($instances as $instance) {
            $this->assertInstanceOf(Terlik::class, $instance);
        }
    }

    #[Test]
    public function warmupEachInstanceWorksIndependently(): void
    {
        $instances = Terlik::warmup();

        $this->assertTrue($instances['tr']->containsProfanity('siktir'));
        $this->assertTrue($instances['en']->containsProfanity('fuck'));
        $this->assertTrue($instances['es']->containsProfanity('mierda'));
        $this->assertTrue($instances['de']->containsProfanity('scheiße'));

        // Cross-language should NOT detect
        $this->assertFalse($instances['tr']->containsProfanity('fuck'));
        $this->assertFalse($instances['en']->containsProfanity('siktir'));
    }

    #[Test]
    public function warmupPassesBaseOptionsStrictMode(): void
    {
        $instances = Terlik::warmup(null, new TerlikOptions(mode: Mode::Strict));

        // Strict mode instances should still detect exact matches
        $this->assertTrue($instances['tr']->containsProfanity('siktir'));
        $this->assertTrue($instances['en']->containsProfanity('fuck'));
    }

    #[Test]
    public function warmupThrowsForUnsupportedLanguage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('xx');

        Terlik::warmup(['xx']);
    }
}
