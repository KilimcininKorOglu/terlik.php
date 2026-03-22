<?php

declare(strict_types=1);

namespace Terlik\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Terlik\Lang\Registry;
use Terlik\MaskStyle;
use Terlik\Mode;
use Terlik\Terlik;
use Terlik\TerlikCore;
use Terlik\TerlikOptions;

final class EntryPointsTest extends TestCase
{
    // ─── Per-language via TerlikOptions ──────────────────────────

    #[Test]
    public function testTrDetectsSiktir(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'tr'));

        $this->assertTrue($terlik->containsProfanity('siktir'));

        $cleaned = $terlik->clean('siktir');
        $this->assertStringNotContainsString('siktir', $cleaned);

        $this->assertSame('tr', $terlik->language);

        $config = Registry::getLanguageConfig('tr');
        $this->assertSame('tr', $config->locale);
    }

    #[Test]
    public function testEnDetectsFuck(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'en'));

        $this->assertTrue($terlik->containsProfanity('fuck'));
        $this->assertSame('en', $terlik->language);

        $config = Registry::getLanguageConfig('en');
        $this->assertSame('en', $config->locale);
    }

    #[Test]
    public function testEsDetectsMierda(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'es'));

        $this->assertTrue($terlik->containsProfanity('mierda'));
        $this->assertSame('es', $terlik->language);

        $config = Registry::getLanguageConfig('es');
        $this->assertSame('es', $config->locale);
    }

    #[Test]
    public function testDeDetectsScheisse(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'de'));

        $this->assertTrue($terlik->containsProfanity('scheiße'));
        $this->assertSame('de', $terlik->language);

        $config = Registry::getLanguageConfig('de');
        $this->assertSame('de', $config->locale);
    }

    // ─── Main entry backward compatibility ──────────────────────

    #[Test]
    public function testTerlikWorksWithAllFourLanguages(): void
    {
        $languages = ['tr', 'en', 'es', 'de'];

        foreach ($languages as $lang) {
            $terlik = new Terlik(new TerlikOptions(language: $lang));
            $this->assertSame($lang, $terlik->language, "Language should be '$lang'");
        }
    }

    #[Test]
    public function testDefaultsToTurkish(): void
    {
        $terlik = new Terlik();
        $this->assertSame('tr', $terlik->language);
        $this->assertTrue($terlik->containsProfanity('siktir'));
    }

    #[Test]
    public function testWarmupStillWorks(): void
    {
        $instances = Terlik::warmup();

        $this->assertIsArray($instances);
        $this->assertNotEmpty($instances);

        foreach ($instances as $lang => $instance) {
            $this->assertInstanceOf(Terlik::class, $instance);
            $this->assertSame($lang, $instance->language);
        }
    }

    #[Test]
    public function testGetSupportedLanguagesReturnsFourLanguages(): void
    {
        $languages = Registry::getSupportedLanguages();

        $this->assertSame(['tr', 'en', 'es', 'de'], $languages);
    }

    #[Test]
    public function testGetLanguageConfigForTrWorks(): void
    {
        $config = Registry::getLanguageConfig('tr');

        $this->assertSame('tr', $config->locale);
        $this->assertIsArray($config->dictionary);
        $this->assertIsArray($config->charMap);
        $this->assertIsArray($config->leetMap);
        $this->assertIsArray($config->charClasses);
    }

    #[Test]
    public function testThrowsForUnsupportedLanguage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Registry::getLanguageConfig('xx');
    }

    // ─── TerlikCore usage ───────────────────────────────────────

    #[Test]
    public function testTerlikCoreClassExists(): void
    {
        $this->assertTrue(class_exists(TerlikCore::class));
    }

    #[Test]
    public function testTerlikCoreCanBeUsedWithManualLanguageConfig(): void
    {
        $config = Registry::getLanguageConfig('tr');
        $core = new TerlikCore($config);

        $this->assertTrue($core->containsProfanity('siktir'));
        $this->assertFalse($core->containsProfanity('merhaba'));
        $this->assertSame('tr', $core->language);
    }

    #[Test]
    public function testTerlikExtendsTerlikCore(): void
    {
        $terlik = new Terlik();

        $this->assertInstanceOf(TerlikCore::class, $terlik);
    }
}
