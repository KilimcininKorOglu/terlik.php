<?php

declare(strict_types=1);

namespace Terlik\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Terlik\Terlik;
use Terlik\TerlikOptions;

/**
 * SPDG Automated Tests
 * Synthetic Profanity Dataset Generator çıktılarını
 * terlik.php detection motoruna karşı test eder.
 * JSONL yoksa ilgili dil bloğu sessizce atlanır.
 */
final class SpdgAutomatedTest extends TestCase
{
    private const LANGUAGES = ['tr', 'en', 'es', 'de'];

    /** Pozitif detection rate threshold'ları (difficulty bazında) */
    private const POSITIVE_THRESHOLDS = [
        'easy' => 80,
        'medium' => 65,
        'hard' => 35,
        'extreme' => null, // sadece rapor, fail etmez
    ];

    /** Negatif false positive üst sınırı (%) */
    private const FALSE_POSITIVE_LIMIT = 5;

    private static function spdgOutputDir(): string
    {
        // Look for SPDG output relative to project root
        return dirname(__DIR__) . '/tools/Synthetic-Profanity-Dataset-Generator/output';
    }

    private static function jsonlPath(string $lang): string
    {
        return self::spdgOutputDir() . "/export-{$lang}.jsonl";
    }

    /**
     * @return array<int, array{text: string, label: int, root: string, difficulty: string, transforms: string[], category: string}>
     */
    private static function parseJsonl(string $filePath): array
    {
        $raw = file_get_contents($filePath);
        if ($raw === false) {
            return [];
        }

        $entries = [];
        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $entry = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            $entries[] = $entry;
        }

        return $entries;
    }

    #[Test]
    public function trPositiveDetection(): void
    {
        $this->runPositiveDetection('tr');
    }

    #[Test]
    public function trNegativeFalsePositives(): void
    {
        $this->runNegativeDetection('tr');
    }

    #[Test]
    public function enPositiveDetection(): void
    {
        $this->runPositiveDetection('en');
    }

    #[Test]
    public function enNegativeFalsePositives(): void
    {
        $this->runNegativeDetection('en');
    }

    #[Test]
    public function esPositiveDetection(): void
    {
        $this->runPositiveDetection('es');
    }

    #[Test]
    public function esNegativeFalsePositives(): void
    {
        $this->runNegativeDetection('es');
    }

    #[Test]
    public function dePositiveDetection(): void
    {
        $this->runPositiveDetection('de');
    }

    #[Test]
    public function deNegativeFalsePositives(): void
    {
        $this->runNegativeDetection('de');
    }

    private function runPositiveDetection(string $lang): void
    {
        $filePath = self::jsonlPath($lang);
        if (!file_exists($filePath)) {
            $this->markTestSkipped("SPDG dataset not found: {$filePath}");
        }

        $entries = self::parseJsonl($filePath);
        $terlik = new Terlik(new TerlikOptions(language: $lang));

        $positives = array_filter($entries, static fn(array $e) => $e['label'] === 1);

        // Group by difficulty
        $byDifficulty = [];
        foreach ($positives as $entry) {
            $diff = $entry['difficulty'];
            $byDifficulty[$diff][] = $entry;
        }

        foreach ($byDifficulty as $difficulty => $group) {
            $detected = 0;
            foreach ($group as $entry) {
                if ($terlik->containsProfanity($entry['text'])) {
                    $detected++;
                }
            }
            $rate = ($detected / count($group)) * 100;
            $threshold = self::POSITIVE_THRESHOLDS[$difficulty] ?? null;

            if ($threshold !== null) {
                $this->assertGreaterThanOrEqual(
                    $threshold,
                    $rate,
                    sprintf(
                        '[%s] %s detection rate %.1f%% < threshold %d%%',
                        strtoupper($lang),
                        $difficulty,
                        $rate,
                        $threshold,
                    ),
                );
            }
        }
    }

    private function runNegativeDetection(string $lang): void
    {
        $filePath = self::jsonlPath($lang);
        if (!file_exists($filePath)) {
            $this->markTestSkipped("SPDG dataset not found: {$filePath}");
        }

        $entries = self::parseJsonl($filePath);
        $terlik = new Terlik(new TerlikOptions(language: $lang));

        $negatives = array_filter($entries, static fn(array $e) => $e['label'] === 0);

        if (empty($negatives)) {
            $this->assertTrue(true);

            return;
        }

        $falsePositives = 0;
        foreach ($negatives as $entry) {
            if ($terlik->containsProfanity($entry['text'])) {
                $falsePositives++;
            }
        }

        $fpRate = ($falsePositives / count($negatives)) * 100;

        $this->assertLessThan(
            self::FALSE_POSITIVE_LIMIT,
            $fpRate,
            sprintf(
                '[%s] False positive rate %.1f%% >= %d%%',
                strtoupper($lang),
                $fpRate,
                self::FALSE_POSITIVE_LIMIT,
            ),
        );
    }
}
