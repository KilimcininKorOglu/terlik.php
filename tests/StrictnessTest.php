<?php

declare(strict_types=1);

namespace Terlik\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Terlik\Terlik;
use Terlik\TerlikOptions;
use Terlik\DetectOptions;
use Terlik\CleanOptions;
use Terlik\Mode;
use Terlik\Severity;
use Terlik\Category;

final class StrictnessTest extends TestCase
{
    // ──────────────────────────────────────────────
    //  disableLeetDecode
    // ──────────────────────────────────────────────

    #[Test]
    public function disableLeetDecodeDefaultCatchesLeetSpeak(): void
    {
        $terlik = new Terlik();
        $this->assertTrue($terlik->containsProfanity('$1kt1r'));
    }

    #[Test]
    public function disableLeetDecodeConstructorTogglePreventsLeetDetection(): void
    {
        $terlik = new Terlik(new TerlikOptions(disableLeetDecode: true));
        $this->assertFalse($terlik->containsProfanity('$1kt1r'));
    }

    #[Test]
    public function disableLeetDecodePerCallOverride(): void
    {
        $terlik = new Terlik();

        // Per-call override disables leet detection
        $this->assertFalse(
            $terlik->containsProfanity('$1kt1r', new DetectOptions(disableLeetDecode: true))
        );

        // Default call still detects leet
        $this->assertTrue($terlik->containsProfanity('$1kt1r'));
    }

    #[Test]
    public function disableLeetDecodeSafetyLayersStayActiveFullwidth(): void
    {
        $terlik = new Terlik(new TerlikOptions(disableLeetDecode: true));
        $this->assertTrue($terlik->containsProfanity('ｓｉｋｔｉｒ'));
    }

    #[Test]
    public function disableLeetDecodeSafetyLayersStayActiveDiacritics(): void
    {
        $terlik = new Terlik(new TerlikOptions(disableLeetDecode: true));
        $this->assertTrue($terlik->containsProfanity('sïktïr'));
    }

    #[Test]
    public function disableLeetDecodeSafetyLayersStayActiveCyrillic(): void
    {
        $terlik = new Terlik(new TerlikOptions(disableLeetDecode: true));
        $this->assertTrue($terlik->containsProfanity('оrоspu'));
    }

    #[Test]
    public function disableLeetDecodeCharClassPass1PlainSiktirStillCaught(): void
    {
        $terlik = new Terlik(new TerlikOptions(disableLeetDecode: true));
        $this->assertTrue($terlik->containsProfanity('siktir'));
    }

    #[Test]
    public function disableLeetDecodePlainProfanityStillDetectedAmk(): void
    {
        $terlik = new Terlik(new TerlikOptions(disableLeetDecode: true));
        $this->assertTrue($terlik->containsProfanity('amk'));
    }

    #[Test]
    public function disableLeetDecodePlainProfanityStillDetectedOrospu(): void
    {
        $terlik = new Terlik(new TerlikOptions(disableLeetDecode: true));
        $this->assertTrue($terlik->containsProfanity('orospu'));
    }

    // ──────────────────────────────────────────────
    //  disableCompound (EN)
    // ──────────────────────────────────────────────

    #[Test]
    public function disableCompoundDefaultDetectsCompound(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'en'));
        $this->assertTrue($terlik->containsProfanity('ShitPerson'));
    }

    #[Test]
    public function disableCompoundConstructorTogglePreventsCompound(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'en', disableCompound: true));
        $this->assertFalse($terlik->containsProfanity('ShitPerson'));
    }

    #[Test]
    public function disableCompoundPerCallOverride(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'en'));

        $this->assertFalse(
            $terlik->containsProfanity('ShitPerson', new DetectOptions(disableCompound: true))
        );
    }

    #[Test]
    public function disableCompoundExplicitVariantsUnaffectedMotherfucker(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'en', disableCompound: true));
        $this->assertTrue($terlik->containsProfanity('motherfucker'));
    }

    #[Test]
    public function disableCompoundExplicitVariantsUnaffectedFuckyou(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'en', disableCompound: true));
        $this->assertTrue($terlik->containsProfanity('fuckyou'));
    }

    #[Test]
    public function disableCompoundPlainProfanityFuck(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'en', disableCompound: true));
        $this->assertTrue($terlik->containsProfanity('fuck'));
    }

    #[Test]
    public function disableCompoundPlainProfanityShit(): void
    {
        $terlik = new Terlik(new TerlikOptions(language: 'en', disableCompound: true));
        $this->assertTrue($terlik->containsProfanity('shit'));
    }

    // ──────────────────────────────────────────────
    //  minSeverity
    // ──────────────────────────────────────────────

    #[Test]
    public function minSeverityDefaultDetectsAllSeverities(): void
    {
        $terlik = new Terlik();

        $this->assertTrue($terlik->containsProfanity('salak'));   // low
        $this->assertTrue($terlik->containsProfanity('bok'));     // medium
        $this->assertTrue($terlik->containsProfanity('siktir')); // high
    }

    #[Test]
    public function minSeverityConstructorMedium(): void
    {
        $terlik = new Terlik(new TerlikOptions(minSeverity: Severity::Medium));

        $this->assertFalse($terlik->containsProfanity('salak'));  // low filtered
        $this->assertTrue($terlik->containsProfanity('bok'));     // medium passes
        $this->assertTrue($terlik->containsProfanity('siktir')); // high passes
    }

    #[Test]
    public function minSeverityConstructorHigh(): void
    {
        $terlik = new Terlik(new TerlikOptions(minSeverity: Severity::High));

        $this->assertFalse($terlik->containsProfanity('salak'));  // low filtered
        $this->assertFalse($terlik->containsProfanity('bok'));    // medium filtered
        $this->assertTrue($terlik->containsProfanity('siktir')); // high passes
    }

    #[Test]
    public function minSeverityPerCallOverride(): void
    {
        $terlik = new Terlik();

        // Per-call medium: salak (low) should be filtered
        $this->assertFalse(
            $terlik->containsProfanity('salak', new DetectOptions(minSeverity: Severity::Medium))
        );

        // Default call: salak still detected
        $this->assertTrue($terlik->containsProfanity('salak'));
    }

    #[Test]
    public function minSeverityLowEquivalentToNoFilter(): void
    {
        $terlik = new Terlik(new TerlikOptions(minSeverity: Severity::Low));

        $this->assertTrue($terlik->containsProfanity('salak'));   // low
        $this->assertTrue($terlik->containsProfanity('bok'));     // medium
        $this->assertTrue($terlik->containsProfanity('siktir')); // high
    }

    // ──────────────────────────────────────────────
    //  excludeCategories
    // ──────────────────────────────────────────────

    #[Test]
    public function excludeCategoriesDefaultDetectsAllCategories(): void
    {
        $terlik = new Terlik();

        $this->assertTrue($terlik->containsProfanity('siktir')); // sexual
        $this->assertTrue($terlik->containsProfanity('orospu')); // insult
        $this->assertTrue($terlik->containsProfanity('ibne'));    // slur
        $this->assertTrue($terlik->containsProfanity('bok'));     // general
    }

    #[Test]
    public function excludeCategoriesExcludeSexual(): void
    {
        $terlik = new Terlik(new TerlikOptions(excludeCategories: [Category::Sexual]));

        $this->assertFalse($terlik->containsProfanity('siktir')); // sexual excluded
        $this->assertTrue($terlik->containsProfanity('orospu'));   // insult passes
        $this->assertTrue($terlik->containsProfanity('bok'));      // general passes
    }

    #[Test]
    public function excludeCategoriesExcludeMultiple(): void
    {
        $terlik = new Terlik(new TerlikOptions(
            excludeCategories: [Category::Sexual, Category::Slur]
        ));

        $this->assertFalse($terlik->containsProfanity('siktir')); // sexual excluded
        $this->assertFalse($terlik->containsProfanity('ibne'));    // slur excluded
        $this->assertTrue($terlik->containsProfanity('orospu'));   // insult passes
        $this->assertTrue($terlik->containsProfanity('bok'));      // general passes
    }

    #[Test]
    public function excludeCategoriesPerCallOverride(): void
    {
        $terlik = new Terlik();

        // Per-call exclude sexual
        $this->assertFalse(
            $terlik->containsProfanity('siktir', new DetectOptions(excludeCategories: [Category::Sexual]))
        );

        // Default call: siktir still detected
        $this->assertTrue($terlik->containsProfanity('siktir'));
    }

    #[Test]
    public function excludeCategoriesCustomWordsNeverExcluded(): void
    {
        $terlik = new Terlik(new TerlikOptions(
            customList: ['testword'],
            excludeCategories: [Category::Sexual, Category::Insult, Category::Slur, Category::General],
        ));

        // Custom words have null category, so they are never excluded
        $this->assertTrue($terlik->containsProfanity('testword'));
    }

    // ──────────────────────────────────────────────
    //  category in MatchResult
    // ──────────────────────────────────────────────

    #[Test]
    public function matchResultIncludesCategorySexualForSiktir(): void
    {
        $terlik = new Terlik();
        $matches = $terlik->getMatches('siktir');

        $this->assertNotEmpty($matches);
        $this->assertSame(Category::Sexual, $matches[0]->category);
    }

    #[Test]
    public function matchResultDifferentCategoriesInOrospuSalakBok(): void
    {
        $terlik = new Terlik();
        $matches = $terlik->getMatches('orospu salak bok');

        $this->assertCount(3, $matches);

        $categories = [];
        foreach ($matches as $match) {
            $categories[$match->root] = $match->category;
        }

        $this->assertSame(Category::Insult, $categories['orospu']);
        $this->assertSame(Category::Insult, $categories['salak']);
        $this->assertSame(Category::General, $categories['bok']);
    }

    #[Test]
    public function matchResultCustomWordsHaveNullCategory(): void
    {
        $terlik = new Terlik(new TerlikOptions(customList: ['testword']));
        $matches = $terlik->getMatches('testword');

        $this->assertNotEmpty($matches);
        $this->assertNull($matches[0]->category);
    }

    // ──────────────────────────────────────────────
    //  mode + toggle interaction
    // ──────────────────────────────────────────────

    #[Test]
    public function modeToggleStrictPlusMinSeverityHigh(): void
    {
        $terlik = new Terlik(new TerlikOptions(
            mode: Mode::Strict,
            minSeverity: Severity::High,
        ));

        $this->assertFalse($terlik->containsProfanity('salak'));  // low filtered
        $this->assertTrue($terlik->containsProfanity('siktir')); // high passes
    }

    #[Test]
    public function modeToggleLoosePlusExcludeSexual(): void
    {
        $terlik = new Terlik(new TerlikOptions(
            mode: Mode::Loose,
            excludeCategories: [Category::Sexual],
        ));

        $this->assertFalse($terlik->containsProfanity('siktir')); // sexual excluded
        $this->assertTrue($terlik->containsProfanity('orospu'));   // insult passes
    }

    #[Test]
    public function modeTogglePerCallModePlusConstructorToggle(): void
    {
        $terlik = new Terlik(new TerlikOptions(minSeverity: Severity::High));

        // Per-call strict mode, but constructor minSeverity=high is still active
        $this->assertFalse(
            $terlik->containsProfanity('salak', new DetectOptions(mode: Mode::Strict))
        );
        $this->assertTrue(
            $terlik->containsProfanity('siktir', new DetectOptions(mode: Mode::Strict))
        );
    }

    #[Test]
    public function modeTogglePerCallToggleOverridesConstructor(): void
    {
        $terlik = new Terlik(new TerlikOptions(minSeverity: Severity::High));

        // Per-call overrides minSeverity to low, so salak should pass
        $this->assertTrue(
            $terlik->containsProfanity('salak', new DetectOptions(minSeverity: Severity::Low))
        );
    }

    // ──────────────────────────────────────────────
    //  Default behavior preservation
    // ──────────────────────────────────────────────

    #[Test]
    public function defaultBehaviorNoOptionsDetectsEverything(): void
    {
        $terlik = new Terlik();

        $this->assertTrue($terlik->containsProfanity('salak'));   // low
        $this->assertTrue($terlik->containsProfanity('bok'));     // medium
        $this->assertTrue($terlik->containsProfanity('siktir')); // high
        $this->assertTrue($terlik->containsProfanity('orospu')); // insult
        $this->assertTrue($terlik->containsProfanity('ibne'));    // slur
    }

    #[Test]
    public function defaultBehaviorCleanRespectsToggles(): void
    {
        $terlik = new Terlik(new TerlikOptions(minSeverity: Severity::High));
        $cleaned = $terlik->clean('salak siktir');

        // salak (low) should NOT be cleaned; siktir (high) should be masked
        $this->assertStringContainsString('salak', $cleaned);
        $this->assertStringNotContainsString('siktir', $cleaned);
    }

    #[Test]
    public function defaultBehaviorCleanPerCallOverride(): void
    {
        $terlik = new Terlik();

        // Default: clean everything
        $cleanedDefault = $terlik->clean('salak siktir');
        $this->assertStringNotContainsString('salak', $cleanedDefault);
        $this->assertStringNotContainsString('siktir', $cleanedDefault);

        // Per-call: only high severity
        $cleanedOverride = $terlik->clean(
            'salak siktir',
            new CleanOptions(minSeverity: Severity::High)
        );
        $this->assertStringContainsString('salak', $cleanedOverride);
        $this->assertStringNotContainsString('siktir', $cleanedOverride);
    }
}
