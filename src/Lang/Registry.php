<?php

declare(strict_types=1);

namespace Terlik\Lang;

use Terlik\Dictionary\Schema;

final class Registry
{
    private const CORE_DICT_VERSION = 1;

    /** @var array<string, LanguageConfig>|null */
    private static ?array $registry = null;

    /**
     * Loads and caches a language config from its config file + dictionary JSON.
     */
    private static function ensureRegistry(): void
    {
        if (self::$registry !== null) {
            return;
        }

        self::$registry = [];
        $langDir = __DIR__;

        // TR
        self::$registry['tr'] = self::loadConfig($langDir . '/Tr');
        // EN
        self::$registry['en'] = self::loadConfig($langDir . '/En');
        // ES
        self::$registry['es'] = self::loadConfig($langDir . '/Es');
        // DE
        self::$registry['de'] = self::loadConfig($langDir . '/De');
    }

    private static function loadConfig(string $dir): LanguageConfig
    {
        $configFile = $dir . '/Config.php';
        if (!file_exists($configFile)) {
            throw new \RuntimeException(sprintf('Language config file not found: %s', $configFile));
        }

        return require $configFile;
    }

    /**
     * Retrieves the configuration for a supported language.
     *
     * @throws \InvalidArgumentException If the language is not supported or dictionary version is too old.
     */
    public static function getLanguageConfig(string $lang): LanguageConfig
    {
        self::ensureRegistry();

        if (!isset(self::$registry[$lang])) {
            $available = implode(', ', self::getSupportedLanguages());

            throw new \InvalidArgumentException(
                sprintf('Unsupported language: "%s". Available languages: %s', $lang, $available)
            );
        }

        $config = self::$registry[$lang];

        if ($config->dictionary['version'] < self::CORE_DICT_VERSION) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Dictionary version %d for language "%s" is below minimum required version %d. Please update the language pack.',
                    $config->dictionary['version'],
                    $lang,
                    self::CORE_DICT_VERSION,
                )
            );
        }

        return $config;
    }

    /**
     * Returns all available language codes.
     *
     * @return string[]
     */
    public static function getSupportedLanguages(): array
    {
        self::ensureRegistry();

        return array_keys(self::$registry);
    }

    /**
     * Resets the internal cache (useful for testing).
     */
    public static function resetCache(): void
    {
        self::$registry = null;
    }
}
