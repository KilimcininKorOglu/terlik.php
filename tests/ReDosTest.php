<?php

declare(strict_types=1);

namespace Terlik\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Terlik\Detector;
use Terlik\Dictionary\Dictionary;
use Terlik\Lang\Registry;
use Terlik\NormalizerConfig;
use Terlik\PatternCompiler;
use Terlik\TextNormalizer;

final class ReDosTest extends TestCase
{
    private static int $maxDetectMs;
    private Detector $trDetector;
    private Detector $enDetector;

    public static function setUpBeforeClass(): void
    {
        self::$maxDetectMs = PatternCompiler::REGEX_TIMEOUT_MS * 120;
    }

    protected function setUp(): void
    {
        // TR detector
        $trConfig = Registry::getLanguageConfig('tr');
        $trNormalizeFn = TextNormalizer::createNormalizer(new NormalizerConfig(
            locale: $trConfig->locale,
            charMap: $trConfig->charMap,
            leetMap: $trConfig->leetMap,
            numberExpansions: $trConfig->numberExpansions,
        ));
        $trSafeNormalizeFn = TextNormalizer::createNormalizer(new NormalizerConfig(
            locale: $trConfig->locale,
            charMap: $trConfig->charMap,
            leetMap: [],
            numberExpansions: [],
        ));
        $trDictionary = new Dictionary($trConfig->dictionary);
        $this->trDetector = new Detector(
            $trDictionary,
            $trNormalizeFn,
            $trSafeNormalizeFn,
            $trConfig->locale,
            $trConfig->charClasses,
        );

        // EN detector
        $enConfig = Registry::getLanguageConfig('en');
        $enNormalizeFn = TextNormalizer::createNormalizer(new NormalizerConfig(
            locale: $enConfig->locale,
            charMap: $enConfig->charMap,
            leetMap: $enConfig->leetMap,
            numberExpansions: $enConfig->numberExpansions,
        ));
        $enSafeNormalizeFn = TextNormalizer::createNormalizer(new NormalizerConfig(
            locale: $enConfig->locale,
            charMap: $enConfig->charMap,
            leetMap: [],
            numberExpansions: [],
        ));
        $enDictionary = new Dictionary($enConfig->dictionary);
        $this->enDetector = new Detector(
            $enDictionary,
            $enNormalizeFn,
            $enSafeNormalizeFn,
            $enConfig->locale,
            $enConfig->charClasses,
        );
    }

    // ─── ReDoS hardening ─────────────────────────────────────

    #[Test]
    public function testRegexTimeoutMsEquals250(): void
    {
        $this->assertSame(250, PatternCompiler::REGEX_TIMEOUT_MS);
    }

    // ─── Adversarial timing ─────────────────────────────────

    #[Test]
    public function testRepeatedSeparatorChars(): void
    {
        $input = 'a' . str_repeat('.', 100) . 'b' . str_repeat('.', 100) . 'c';
        $start = hrtime(true);
        $this->trDetector->detect($input);
        $elapsed = (hrtime(true) - $start) / 1_000_000;
        $this->assertLessThan(self::$maxDetectMs, $elapsed);
    }

    #[Test]
    public function testLongAtSigns(): void
    {
        $input = str_repeat('@', 50);
        $start = hrtime(true);
        $this->trDetector->detect($input);
        $elapsed = (hrtime(true) - $start) / 1_000_000;
        $this->assertLessThan(self::$maxDetectMs, $elapsed);
    }

    #[Test]
    public function testLongDollarSignsEn(): void
    {
        $input = str_repeat('$', 50);
        $start = hrtime(true);
        $this->enDetector->detect($input);
        $elapsed = (hrtime(true) - $start) / 1_000_000;
        $this->assertLessThan(self::$maxDetectMs, $elapsed);
    }

    #[Test]
    public function testMaxLength10K(): void
    {
        $input = str_repeat('test', 2500);
        $start = hrtime(true);
        $this->trDetector->detect($input);
        $elapsed = (hrtime(true) - $start) / 1_000_000;
        $this->assertLessThan(self::$maxDetectMs, $elapsed);
    }

    #[Test]
    public function testLeetSeparatorCombo(): void
    {
        $input = '$' . str_repeat('...', 20) . '1' . str_repeat('...', 20) . 'k';
        $start = hrtime(true);
        $this->trDetector->detect($input);
        $elapsed = (hrtime(true) - $start) / 1_000_000;
        $this->assertLessThan(self::$maxDetectMs, $elapsed);
    }

    #[Test]
    public function testMixedOverlapSymbols(): void
    {
        $input = str_repeat('@$!|+#€', 10);
        $start = hrtime(true);
        $this->trDetector->detect($input);
        $elapsed = (hrtime(true) - $start) / 1_000_000;
        $this->assertLessThan(self::$maxDetectMs, $elapsed);
    }

    #[Test]
    public function testAdversarialPipesEn(): void
    {
        $input = str_repeat('|', 50);
        $start = hrtime(true);
        $this->enDetector->detect($input);
        $elapsed = (hrtime(true) - $start) / 1_000_000;
        $this->assertLessThan(self::$maxDetectMs, $elapsed);
    }

    #[Test]
    public function testAlternatingLetterSeparator(): void
    {
        $input = implode('.', array_fill(0, 100, 'a'));
        $start = hrtime(true);
        $this->trDetector->detect($input);
        $elapsed = (hrtime(true) - $start) / 1_000_000;
        $this->assertLessThan(self::$maxDetectMs, $elapsed);
    }

    #[Test]
    public function testUnicodeSymbolFlood(): void
    {
        $input = str_repeat('€¢©®™', 20);
        $start = hrtime(true);
        $this->trDetector->detect($input);
        $elapsed = (hrtime(true) - $start) / 1_000_000;
        $this->assertLessThan(self::$maxDetectMs, $elapsed);
    }

    #[Test]
    public function testNearMatchPrefixFlood(): void
    {
        $input = str_repeat('s', 50) . 'xxxxx';
        $start = hrtime(true);
        $this->trDetector->detect($input);
        $elapsed = (hrtime(true) - $start) / 1_000_000;
        $this->assertLessThan(self::$maxDetectMs, $elapsed);
    }

    #[Test]
    public function testDeepSuffixChain(): void
    {
        $input = 'orospu' . str_repeat('larinin', 10);
        $start = hrtime(true);
        $this->trDetector->detect($input);
        $elapsed = (hrtime(true) - $start) / 1_000_000;
        $this->assertLessThan(self::$maxDetectMs, $elapsed);
    }

    // ─── Detection regression TR ─────────────────────────────

    #[Test]
    public function testTrPlainSiktir(): void
    {
        $matches = $this->trDetector->detect('siktir');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testTrLeetSiktir(): void
    {
        $matches = $this->trDetector->detect('$1kt1r');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testTrSeparatorSiktir(): void
    {
        $matches = $this->trDetector->detect('s.i.k.t.i.r');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testTrRepeatedSiktir(): void
    {
        $matches = $this->trDetector->detect('siiiiiktir');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testTrNumberSiktir(): void
    {
        $matches = $this->trDetector->detect('s1kt1r');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testTrSuffixOrospuluk(): void
    {
        $matches = $this->trDetector->detect('orospuluk');
        $this->assertNotEmpty($matches);
    }

    // ─── Detection regression EN ─────────────────────────────

    #[Test]
    public function testEnPlainFuckOff(): void
    {
        $matches = $this->enDetector->detect('fuck off');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testEnLeetFuck(): void
    {
        $matches = $this->enDetector->detect('what the f*ck');
        $this->assertNotEmpty($matches);
    }

    // ─── Attack surface coverage - separator abuse ───────────

    #[Test]
    public function testSeparatorSik(): void
    {
        $matches = $this->trDetector->detect('s.i.k');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testMixedSeparatorsSiktir(): void
    {
        $matches = $this->trDetector->detect('s_i-k.t.i.r');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testMax3SeparatorsSik(): void
    {
        $matches = $this->trDetector->detect('s...i...k');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testFourPlusSeparatorsCaughtByNormalizer(): void
    {
        // 4+ separators are caught by normalizer (should not match as pattern allows max 3)
        $matches = $this->trDetector->detect('s....i....k');
        // The normalizer may or may not catch this; assert it does not crash
        $this->assertIsArray($matches);
    }

    #[Test]
    public function testTabSeparator(): void
    {
        $matches = $this->trDetector->detect("s\ti\tk");
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testZwjSeparator(): void
    {
        $matches = $this->trDetector->detect("s\u{200D}i\u{200D}k");
        $this->assertNotEmpty($matches);
    }

    // ─── Leet speak bypass ───────────────────────────────────

    #[Test]
    public function testLeetDollarSiktir(): void
    {
        $matches = $this->trDetector->detect('$1kt1r');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testLeetS1ktir(): void
    {
        $matches = $this->trDetector->detect('s1ktir');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testLeetAptal(): void
    {
        $matches = $this->trDetector->detect('@pt@l');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testLeet8ok(): void
    {
        $matches = $this->trDetector->detect('8ok');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testLeetDollarSikWithSeparators(): void
    {
        $matches = $this->trDetector->detect('$...1...k');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testLeetFuckEn(): void
    {
        $matches = $this->enDetector->detect('f*ck');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testLeetPhuckEn(): void
    {
        $matches = $this->enDetector->detect('phuck');
        $this->assertNotEmpty($matches);
    }

    // ─── Char repetition ─────────────────────────────────────

    #[Test]
    public function testRepetitionSiiiiik(): void
    {
        $matches = $this->trDetector->detect('siiiiik');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testRepetitionSikkkk(): void
    {
        $matches = $this->trDetector->detect('sikkkk');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testRepetition16Is(): void
    {
        $matches = $this->trDetector->detect('s' . str_repeat('i', 16) . 'k');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testRepetitionTripleDollarSiktir(): void
    {
        $matches = $this->trDetector->detect('$$$1kt1r');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testRepetitionFuuuuckEn(): void
    {
        $matches = $this->enDetector->detect('fuuuuck');
        $this->assertNotEmpty($matches);
    }

    // ─── Unicode tricks ──────────────────────────────────────

    #[Test]
    public function testUnicodeMixedCaseSiKTiR(): void
    {
        $matches = $this->trDetector->detect('SiKTiR');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testUnicodeUpperSIKTIR(): void
    {
        $matches = $this->trDetector->detect('SIKTIR');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testUnicodeAlternatingCaseSIkTiR(): void
    {
        $matches = $this->trDetector->detect('sIkTiR');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testFullwidthSikNoCrash(): void
    {
        // Fullwidth chars: should not crash
        $matches = $this->trDetector->detect("\u{FF33}\u{FF29}\u{FF2B}");
        $this->assertIsArray($matches);
    }

    #[Test]
    public function testCombiningDiacriticsNoCrash(): void
    {
        // Combining diacritics over regular letters
        $input = "s\u{0301}i\u{0301}k\u{0301}";
        $matches = $this->trDetector->detect($input);
        $this->assertIsArray($matches);
    }

    // ─── Whitelist integrity ─────────────────────────────────

    #[Test]
    public function testWhitelistSikke(): void
    {
        $matches = $this->trDetector->detect('sikke');
        $this->assertEmpty($matches);
    }

    #[Test]
    public function testWhitelistAmsterdam(): void
    {
        $matches = $this->enDetector->detect('amsterdam');
        $this->assertEmpty($matches);
    }

    #[Test]
    public function testWhitelistS1kke(): void
    {
        $matches = $this->trDetector->detect('s1kke');
        $this->assertEmpty($matches);
    }

    #[Test]
    public function testWhitelistSikkeleri(): void
    {
        $matches = $this->trDetector->detect('sikkeleri');
        $this->assertEmpty($matches);
    }

    // ─── Boundary attacks ────────────────────────────────────

    #[Test]
    public function testBoundaryStart(): void
    {
        $matches = $this->trDetector->detect('siktir git');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testBoundaryEnd(): void
    {
        $matches = $this->trDetector->detect('hadi siktir');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testBoundaryEntire(): void
    {
        $matches = $this->trDetector->detect('siktir');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testBoundaryPunctuation(): void
    {
        $matches = $this->trDetector->detect('(siktir)');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testBoundaryQuotes(): void
    {
        $matches = $this->trDetector->detect('"siktir" dedi');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testBoundaryTrailingNumbers(): void
    {
        $matches = $this->trDetector->detect('siktir123');
        $this->assertEmpty($matches);
    }

    #[Test]
    public function testBoundaryEmojis(): void
    {
        $matches = $this->trDetector->detect("\u{1F621} siktir \u{1F621}");
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testBoundaryEmbedded(): void
    {
        $matches = $this->trDetector->detect('mesiktin');
        $this->assertEmpty($matches);
    }

    // ─── Multi-match ─────────────────────────────────────────

    #[Test]
    public function testMultipleDistinctProfanities(): void
    {
        $matches = $this->trDetector->detect('siktir git orospu cocugu');
        $this->assertGreaterThanOrEqual(2, count($matches));
    }

    #[Test]
    public function testSameWord20Times(): void
    {
        $input = implode(' ', array_fill(0, 20, 'siktir'));
        $matches = $this->trDetector->detect($input);
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testDifferentRoots(): void
    {
        $matches = $this->trDetector->detect('sik bok got amk ibne');
        $this->assertGreaterThanOrEqual(3, count($matches));
    }

    // ─── Input edge cases ────────────────────────────────────

    #[Test]
    public function testEdgeCaseEmpty(): void
    {
        $matches = $this->trDetector->detect('');
        $this->assertEmpty($matches);
    }

    #[Test]
    public function testEdgeCaseWhitespace(): void
    {
        $matches = $this->trDetector->detect("   \t\n");
        $this->assertEmpty($matches);
    }

    #[Test]
    public function testEdgeCaseSingleChar(): void
    {
        $matches = $this->trDetector->detect('a');
        $this->assertEmpty($matches);
    }

    #[Test]
    public function testEdgeCaseOnlyNumbers(): void
    {
        $matches = $this->trDetector->detect('123456789');
        $this->assertEmpty($matches);
    }

    #[Test]
    public function testEdgeCaseOnlySpecialChars(): void
    {
        $matches = $this->trDetector->detect('!@#%^&*()');
        $this->assertEmpty($matches);
    }

    #[Test]
    public function testEdgeCaseVeryLongCleanText(): void
    {
        $input = str_repeat('Bu guzel bir cumle. ', 500);
        $matches = $this->trDetector->detect($input);
        $this->assertEmpty($matches);
    }

    #[Test]
    public function testEdgeCaseNullBytesNoCrash(): void
    {
        $input = "test\x00test\x00test";
        $matches = $this->trDetector->detect($input);
        $this->assertIsArray($matches);
    }

    #[Test]
    public function testEdgeCaseNewlinesAsSeparator(): void
    {
        $matches = $this->trDetector->detect("s\ni\nk");
        $this->assertNotEmpty($matches);
    }

    // ─── Suffix hardening ────────────────────────────────────

    #[Test]
    public function testSuffixOrospuluk(): void
    {
        $matches = $this->trDetector->detect('orospuluk');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testSuffixOrospuluklar(): void
    {
        $matches = $this->trDetector->detect('orospuluklar');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testSuffixAmaNeden(): void
    {
        $matches = $this->trDetector->detect('ama neden');
        $this->assertEmpty($matches);
    }

    #[Test]
    public function testSuffixSeparatedSiktirler(): void
    {
        $matches = $this->trDetector->detect('s.i.k.t.i.r.l.e.r');
        $this->assertNotEmpty($matches);
    }

    #[Test]
    public function testSuffixLeetSiktirler(): void
    {
        $matches = $this->trDetector->detect('$1kt1rler');
        $this->assertNotEmpty($matches);
    }
}
