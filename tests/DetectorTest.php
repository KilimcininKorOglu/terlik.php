<?php

declare(strict_types=1);

namespace Terlik\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Terlik\DetectOptions;
use Terlik\Detector;
use Terlik\Dictionary\Dictionary;
use Terlik\FuzzyAlgorithm;
use Terlik\Lang\LanguageConfig;
use Terlik\Lang\Registry;
use Terlik\MatchMethod;
use Terlik\Mode;
use Terlik\NormalizerConfig;
use Terlik\TextNormalizer;

final class DetectorTest extends TestCase
{
    private Detector $detector;

    protected function setUp(): void
    {
        $langConfig = Registry::getLanguageConfig('tr');

        $normalizeFn = TextNormalizer::createNormalizer(new NormalizerConfig(
            locale: $langConfig->locale,
            charMap: $langConfig->charMap,
            leetMap: $langConfig->leetMap,
            numberExpansions: $langConfig->numberExpansions,
        ));

        $safeNormalizeFn = TextNormalizer::createNormalizer(new NormalizerConfig(
            locale: $langConfig->locale,
            charMap: $langConfig->charMap,
            leetMap: [],
            numberExpansions: [],
        ));

        $dictionary = new Dictionary($langConfig->dictionary);

        $this->detector = new Detector(
            $dictionary,
            $normalizeFn,
            $safeNormalizeFn,
            $langConfig->locale,
            $langConfig->charClasses,
        );
    }

    // ─── Pattern mode (balanced) ─────────────────────────────

    #[Test]
    public function testPatternModeDetectsPlainProfanity(): void
    {
        $matches = $this->detector->detect('siktir git', new DetectOptions(mode: Mode::Balanced));

        $this->assertNotEmpty($matches);
        $this->assertSame('sik', $matches[0]->root);
    }

    #[Test]
    public function testPatternModeDetectsLeetSpeak(): void
    {
        $matches = $this->detector->detect('$1kt1r', new DetectOptions(mode: Mode::Balanced));

        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testPatternModeDetectsSeparators(): void
    {
        $matches = $this->detector->detect('s.i.k.t.i.r', new DetectOptions(mode: Mode::Balanced));

        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testPatternModeDetectsRepeatedChars(): void
    {
        $matches = $this->detector->detect('siiiktir', new DetectOptions(mode: Mode::Balanced));

        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testPatternModeWhitelistExcludesSikke(): void
    {
        $langConfig = Registry::getLanguageConfig('tr');

        $normalizeFn = TextNormalizer::createNormalizer(new NormalizerConfig(
            locale: $langConfig->locale,
            charMap: $langConfig->charMap,
            leetMap: $langConfig->leetMap,
            numberExpansions: $langConfig->numberExpansions,
        ));

        $safeNormalizeFn = TextNormalizer::createNormalizer(new NormalizerConfig(
            locale: $langConfig->locale,
            charMap: $langConfig->charMap,
            leetMap: [],
            numberExpansions: [],
        ));

        // "sikke" is in the default whitelist
        $dictionary = new Dictionary($langConfig->dictionary);
        $detector = new Detector(
            $dictionary,
            $normalizeFn,
            $safeNormalizeFn,
            $langConfig->locale,
            $langConfig->charClasses,
        );

        $matches = $detector->detect('sikke', new DetectOptions(mode: Mode::Balanced));
        $this->assertEmpty($matches);
    }

    #[Test]
    public function testPatternModeDetectsOrospu(): void
    {
        $matches = $this->detector->detect('orospu', new DetectOptions(mode: Mode::Balanced));

        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testPatternModeReturnsEmptyForCleanText(): void
    {
        $matches = $this->detector->detect('merhaba dunya', new DetectOptions(mode: Mode::Balanced));

        $this->assertEmpty($matches);
    }

    // ─── Strict mode ─────────────────────────────────────────

    #[Test]
    public function testStrictModeDetectsExactSiktirGit(): void
    {
        $matches = $this->detector->detect('siktir git', new DetectOptions(mode: Mode::Strict));

        $this->assertNotEmpty($matches);
        $found = false;
        foreach ($matches as $match) {
            if ($match->method === MatchMethod::Exact) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Expected at least one exact match in strict mode');
    }

    #[Test]
    public function testStrictModeDoesNotCatchSpacedLetters(): void
    {
        $matches = $this->detector->detect('s i k t i r', new DetectOptions(mode: Mode::Strict));

        $this->assertEmpty($matches);
    }

    // ─── Loose mode with fuzzy ───────────────────────────────

    #[Test]
    public function testLooseModeDetectsFuzzyMatch(): void
    {
        $matches = $this->detector->detect('siktiir', new DetectOptions(
            mode: Mode::Loose,
            enableFuzzy: true,
            fuzzyThreshold: 0.8,
            fuzzyAlgorithm: FuzzyAlgorithm::Levenshtein,
        ));

        $this->assertNotEmpty($matches);
    }

    // ─── getPatterns ─────────────────────────────────────────

    #[Test]
    public function testGetPatternsReturnsNonEmptyArray(): void
    {
        $patterns = $this->detector->getPatterns();

        $this->assertIsArray($patterns);
        $this->assertNotEmpty($patterns);
    }

    #[Test]
    public function testGetPatternsContainsSikKey(): void
    {
        $patterns = $this->detector->getPatterns();

        $this->assertArrayHasKey('sik', $patterns);
        $this->assertIsString($patterns['sik']);
    }
}
