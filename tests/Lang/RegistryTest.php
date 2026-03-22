<?php

declare(strict_types=1);

namespace Terlik\Tests\Lang;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Terlik\Lang\LanguageConfig;
use Terlik\Lang\Registry;

final class RegistryTest extends TestCase
{
    protected function setUp(): void
    {
        Registry::resetCache();
    }

    // ──────────────────────────────────────────────
    //  Returns config for all supported languages
    // ──────────────────────────────────────────────

    #[Test]
    public function returnsConfigWithCorrectLocaleForAllLanguages(): void
    {
        foreach (['tr', 'en', 'es', 'de'] as $lang) {
            $config = Registry::getLanguageConfig($lang);

            $this->assertInstanceOf(LanguageConfig::class, $config);
            $this->assertSame($lang, $config->locale);
        }
    }

    #[Test]
    public function returnsConfigWithCharMapForAllLanguages(): void
    {
        foreach (['tr', 'en', 'es', 'de'] as $lang) {
            $config = Registry::getLanguageConfig($lang);

            $this->assertIsArray($config->charMap);
        }
    }

    #[Test]
    public function returnsConfigWithLeetMapForAllLanguages(): void
    {
        foreach (['tr', 'en', 'es', 'de'] as $lang) {
            $config = Registry::getLanguageConfig($lang);

            $this->assertIsArray($config->leetMap);
        }
    }

    #[Test]
    public function returnsConfigWithCharClassesForAllLanguages(): void
    {
        foreach (['tr', 'en', 'es', 'de'] as $lang) {
            $config = Registry::getLanguageConfig($lang);

            $this->assertIsArray($config->charClasses);
            $this->assertNotEmpty($config->charClasses);
        }
    }

    #[Test]
    public function returnsConfigWithValidDictionaryForAllLanguages(): void
    {
        foreach (['tr', 'en', 'es', 'de'] as $lang) {
            $config = Registry::getLanguageConfig($lang);

            $this->assertIsArray($config->dictionary);
            $this->assertArrayHasKey('version', $config->dictionary);
            $this->assertArrayHasKey('entries', $config->dictionary);
            $this->assertArrayHasKey('suffixes', $config->dictionary);
            $this->assertArrayHasKey('whitelist', $config->dictionary);
            $this->assertGreaterThanOrEqual(1, $config->dictionary['version']);
            $this->assertNotEmpty($config->dictionary['entries']);
        }
    }

    // ──────────────────────────────────────────────
    //  Throws for unsupported language
    // ──────────────────────────────────────────────

    #[Test]
    public function throwsForUnsupportedLanguage(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Registry::getLanguageConfig('xx');
    }

    #[Test]
    public function errorMessageListsAvailableLanguages(): void
    {
        try {
            Registry::getLanguageConfig('xx');
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('tr', $message);
            $this->assertStringContainsString('en', $message);
            $this->assertStringContainsString('es', $message);
            $this->assertStringContainsString('de', $message);
        }
    }

    // ──────────────────────────────────────────────
    //  getSupportedLanguages
    // ──────────────────────────────────────────────

    #[Test]
    public function getSupportedLanguagesReturnsFourLanguages(): void
    {
        $languages = Registry::getSupportedLanguages();

        $this->assertCount(4, $languages);
        $this->assertContains('tr', $languages);
        $this->assertContains('en', $languages);
        $this->assertContains('es', $languages);
        $this->assertContains('de', $languages);
    }

    // ──────────────────────────────────────────────
    //  Each config has valid charClasses with a/s/t keys
    // ──────────────────────────────────────────────

    #[Test]
    public function eachConfigHasCharClassesWithRequiredKeys(): void
    {
        foreach (['tr', 'en', 'es', 'de'] as $lang) {
            $config = Registry::getLanguageConfig($lang);

            $this->assertArrayHasKey(
                'a',
                $config->charClasses,
                "Language '$lang' should have charClass for 'a'"
            );
            $this->assertArrayHasKey(
                's',
                $config->charClasses,
                "Language '$lang' should have charClass for 's'"
            );
            $this->assertArrayHasKey(
                't',
                $config->charClasses,
                "Language '$lang' should have charClass for 't'"
            );
        }
    }

    // ──────────────────────────────────────────────
    //  Turkish config has numberExpansions
    // ──────────────────────────────────────────────

    #[Test]
    public function turkishConfigHasNumberExpansions(): void
    {
        $config = Registry::getLanguageConfig('tr');

        $this->assertNotNull($config->numberExpansions);
        $this->assertIsArray($config->numberExpansions);
        $this->assertNotEmpty($config->numberExpansions);
    }

    // ──────────────────────────────────────────────
    //  English config has no numberExpansions (null)
    // ──────────────────────────────────────────────

    #[Test]
    public function englishConfigHasNoNumberExpansions(): void
    {
        $config = Registry::getLanguageConfig('en');

        $this->assertNull($config->numberExpansions);
    }
}
