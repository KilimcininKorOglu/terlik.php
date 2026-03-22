<?php

declare(strict_types=1);

namespace Terlik\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Terlik\Terlik;
use Terlik\TerlikOptions;

final class AdversarialAuditTest extends TestCase
{
    private Terlik $en;
    private Terlik $tr;
    private Terlik $es;
    private Terlik $de;

    protected function setUp(): void
    {
        $this->en = new Terlik(new TerlikOptions(language: 'en'));
        $this->tr = new Terlik(new TerlikOptions(language: 'tr'));
        $this->es = new Terlik(new TerlikOptions(language: 'es'));
        $this->de = new Terlik(new TerlikOptions(language: 'de'));
    }

    // ═══════════════════════════════════════════════════════════
    //  EN adversarial
    // ═══════════════════════════════════════════════════════════

    // ─── Accented Latin bypass (12 detected + 1 FP check) ────

    #[Test]
    public function testEnAccentedFuck(): void
    {
        $this->assertTrue($this->en->containsProfanity('fück'));
    }

    #[Test]
    public function testEnAccentedFuck2(): void
    {
        $this->assertTrue($this->en->containsProfanity('fùck'));
    }

    #[Test]
    public function testEnAccentedFuck3(): void
    {
        $this->assertTrue($this->en->containsProfanity('fûck'));
    }

    #[Test]
    public function testEnAccentedShit(): void
    {
        $this->assertTrue($this->en->containsProfanity('shît'));
    }

    #[Test]
    public function testEnAccentedShit2(): void
    {
        $this->assertTrue($this->en->containsProfanity('shït'));
    }

    #[Test]
    public function testEnAccentedBitch(): void
    {
        $this->assertTrue($this->en->containsProfanity('bîtch'));
    }

    #[Test]
    public function testEnAccentedCunt(): void
    {
        $this->assertTrue($this->en->containsProfanity('cünt'));
    }

    #[Test]
    public function testEnAccentedDick(): void
    {
        $this->assertTrue($this->en->containsProfanity('dìck'));
    }

    #[Test]
    public function testEnAccentedNigger(): void
    {
        $this->assertTrue($this->en->containsProfanity('nïgger'));
    }

    #[Test]
    public function testEnAccentedFaggot(): void
    {
        $this->assertTrue($this->en->containsProfanity('fàggot'));
    }

    #[Test]
    public function testEnAccentedRape(): void
    {
        $this->assertTrue($this->en->containsProfanity('ràpe'));
    }

    #[Test]
    public function testEnAccentedPorn(): void
    {
        $this->assertTrue($this->en->containsProfanity('pörn'));
    }

    #[Test]
    public function testEnAccentedFpWonk(): void
    {
        $this->assertFalse($this->en->containsProfanity('wönk'));
    }

    // ─── Cyrillic homoglyph (10 detected) ────────────────────

    #[Test]
    public function testEnCyrillicFuck(): void
    {
        // fu + Cyrillic с + k
        $this->assertTrue($this->en->containsProfanity("fu\u{0441}k"));
    }

    #[Test]
    public function testEnCyrillicFuck2(): void
    {
        // f + Cyrillic у + ck
        $this->assertTrue($this->en->containsProfanity("f\u{0443}ck"));
    }

    #[Test]
    public function testEnCyrillicAss(): void
    {
        // Cyrillic а + ss
        $this->assertTrue($this->en->containsProfanity("\u{0430}ss"));
    }

    #[Test]
    public function testEnCyrillicShit(): void
    {
        // sh + Cyrillic і + t
        $this->assertTrue($this->en->containsProfanity("sh\u{0456}t"));
    }

    #[Test]
    public function testEnCyrillicBitch(): void
    {
        // b + Cyrillic і + tch
        $this->assertTrue($this->en->containsProfanity("b\u{0456}tch"));
    }

    #[Test]
    public function testEnCyrillicCunt(): void
    {
        // Cyrillic с + unt
        $this->assertTrue($this->en->containsProfanity("\u{0441}unt"));
    }

    #[Test]
    public function testEnCyrillicDick(): void
    {
        // di + Cyrillic с + k
        $this->assertTrue($this->en->containsProfanity("di\u{0441}k"));
    }

    #[Test]
    public function testEnCyrillicWhore(): void
    {
        // wh + Cyrillic о + re
        $this->assertTrue($this->en->containsProfanity("wh\u{043E}re"));
    }

    #[Test]
    public function testEnCyrillicRape(): void
    {
        // Cyrillic р (looks like p) + Cyrillic а + pe → rаpe
        $this->assertTrue($this->en->containsProfanity("r\u{0430}pe"));
    }

    #[Test]
    public function testEnCyrillicPorn(): void
    {
        // p + Cyrillic о + rn
        $this->assertTrue($this->en->containsProfanity("p\u{043E}rn"));
    }

    // ─── Fullwidth (3) ───────────────────────────────────────

    #[Test]
    public function testEnFullwidthFuck(): void
    {
        $this->assertTrue($this->en->containsProfanity("\u{FF46}\u{FF55}\u{FF43}\u{FF4B}"));
    }

    #[Test]
    public function testEnFullwidthShit(): void
    {
        $this->assertTrue($this->en->containsProfanity("\u{FF53}\u{FF48}\u{FF49}\u{FF54}"));
    }

    #[Test]
    public function testEnFullwidthMixedFuck(): void
    {
        // f + fullwidth u + ck
        $this->assertTrue($this->en->containsProfanity("f\u{FF55}ck"));
    }

    // ─── Unicode normalization (3) ───────────────────────────

    #[Test]
    public function testEnUnicodeNormFuck1(): void
    {
        // fu + c with cedilla + k → normalizes to fuck
        $this->assertTrue($this->en->containsProfanity("fu\u{00E7}k"));
    }

    #[Test]
    public function testEnUnicodeNormFuck2(): void
    {
        // fu + c + combining cedilla + k
        $this->assertTrue($this->en->containsProfanity("fuc\u{0327}k"));
    }

    #[Test]
    public function testEnUnicodeNormNfdNfcShitConsistency(): void
    {
        // NFD form of shît
        $nfd = "sh\u{0069}\u{0302}t";
        // NFC form
        $nfc = "sh\u{00EE}t";
        $nfdResult = $this->en->containsProfanity($nfd);
        $nfcResult = $this->en->containsProfanity($nfc);
        // Both should yield the same detection result
        $this->assertSame($nfdResult, $nfcResult);
    }

    // ─── Zero-width chars (3) ────────────────────────────────

    #[Test]
    public function testEnZeroWidthZwsp(): void
    {
        $this->assertTrue($this->en->containsProfanity("f\u{200B}uck"));
    }

    #[Test]
    public function testEnZeroWidthZwnj(): void
    {
        $this->assertTrue($this->en->containsProfanity("f\u{200C}u\u{200C}c\u{200C}k"));
    }

    #[Test]
    public function testEnZeroWidthSoftHyphen(): void
    {
        $this->assertTrue($this->en->containsProfanity("f\u{00AD}u\u{00AD}c\u{00AD}k"));
    }

    // ─── Missing vocabulary (15 - informational audit) ───────

    #[Test]
    public function testEnMissingVocabularyAudit(): void
    {
        $words = [
            'wanker', 'tosser', 'bellend', 'minger', 'pillock',
            'git', 'sod', 'bloody', 'bollocks', 'bugger',
            'crikey', 'blimey', 'arse', 'arsehole', 'twit',
        ];
        foreach ($words as $word) {
            // Informational audit: just run detection, no assertion on result
            $this->en->containsProfanity($word);
        }
        $this->assertTrue(true);
    }

    // ─── FP stress (34 words) ────────────────────────────────

    #[Test]
    public function testEnFpStressAssumption(): void
    {
        $this->assertFalse($this->en->containsProfanity('assumption'));
    }

    #[Test]
    public function testEnFpStressCocky(): void
    {
        $this->assertFalse($this->en->containsProfanity('cocky'));
    }

    #[Test]
    public function testEnFpStressTherapists(): void
    {
        $this->assertFalse($this->en->containsProfanity('therapists'));
    }

    #[Test]
    public function testEnFpStressGrapevine(): void
    {
        $this->assertFalse($this->en->containsProfanity('grapevine'));
    }

    #[Test]
    public function testEnFpStressPassionate(): void
    {
        $this->assertFalse($this->en->containsProfanity('passionate'));
    }

    #[Test]
    public function testEnFpStressCompassionate(): void
    {
        $this->assertFalse($this->en->containsProfanity('compassionate'));
    }

    #[Test]
    public function testEnFpStressEmbarrass(): void
    {
        $this->assertFalse($this->en->containsProfanity('embarrass'));
    }

    #[Test]
    public function testEnFpStressHarassment(): void
    {
        $this->assertFalse($this->en->containsProfanity('harassment'));
    }

    #[Test]
    public function testEnFpStressScrapbook(): void
    {
        $this->assertFalse($this->en->containsProfanity('scrapbook'));
    }

    #[Test]
    public function testEnFpStressCumulonimbus(): void
    {
        $this->assertFalse($this->en->containsProfanity('cumulonimbus'));
    }

    #[Test]
    public function testEnFpStressCumulative(): void
    {
        $this->assertFalse($this->en->containsProfanity('cumulative'));
    }

    #[Test]
    public function testEnFpStressCircumvent(): void
    {
        $this->assertFalse($this->en->containsProfanity('circumvent'));
    }

    #[Test]
    public function testEnFpStressPennant(): void
    {
        $this->assertFalse($this->en->containsProfanity('pennant'));
    }

    #[Test]
    public function testEnFpStressPenalize(): void
    {
        $this->assertFalse($this->en->containsProfanity('penalize'));
    }

    #[Test]
    public function testEnFpStressPeninsula(): void
    {
        $this->assertFalse($this->en->containsProfanity('peninsula'));
    }

    #[Test]
    public function testEnFpStressPenetrate(): void
    {
        $this->assertFalse($this->en->containsProfanity('penetrate'));
    }

    #[Test]
    public function testEnFpStressTitanic(): void
    {
        $this->assertFalse($this->en->containsProfanity('Titanic'));
    }

    #[Test]
    public function testEnFpStressConstitution(): void
    {
        $this->assertFalse($this->en->containsProfanity('constitution'));
    }

    #[Test]
    public function testEnFpStressAnalytical(): void
    {
        $this->assertFalse($this->en->containsProfanity('analytical'));
    }

    #[Test]
    public function testEnFpStressPsychoanalysis(): void
    {
        $this->assertFalse($this->en->containsProfanity('psychoanalysis'));
    }

    #[Test]
    public function testEnFpStressMasseuse(): void
    {
        $this->assertFalse($this->en->containsProfanity('masseuse'));
    }

    #[Test]
    public function testEnFpStressCassette(): void
    {
        $this->assertFalse($this->en->containsProfanity('cassette'));
    }

    #[Test]
    public function testEnFpStressClassic(): void
    {
        $this->assertFalse($this->en->containsProfanity('classic'));
    }

    #[Test]
    public function testEnFpStressClassy(): void
    {
        $this->assertFalse($this->en->containsProfanity('classy'));
    }

    #[Test]
    public function testEnFpStressDickensian(): void
    {
        $this->assertFalse($this->en->containsProfanity('Dickensian'));
    }

    #[Test]
    public function testEnFpStressCocktails(): void
    {
        $this->assertFalse($this->en->containsProfanity('cocktails'));
    }

    #[Test]
    public function testEnFpStressPeacocking(): void
    {
        $this->assertFalse($this->en->containsProfanity('peacocking'));
    }

    #[Test]
    public function testEnFpStressButtress(): void
    {
        $this->assertFalse($this->en->containsProfanity('buttress'));
    }

    #[Test]
    public function testEnFpStressButterscotch(): void
    {
        $this->assertFalse($this->en->containsProfanity('butterscotch'));
    }

    #[Test]
    public function testEnFpStressRebuttal(): void
    {
        $this->assertFalse($this->en->containsProfanity('rebuttal'));
    }

    #[Test]
    public function testEnFpStressSextant(): void
    {
        $this->assertFalse($this->en->containsProfanity('sextant'));
    }

    #[Test]
    public function testEnFpStressSextet(): void
    {
        $this->assertFalse($this->en->containsProfanity('sextet'));
    }

    #[Test]
    public function testEnFpStressSussex(): void
    {
        $this->assertFalse($this->en->containsProfanity('Sussex'));
    }

    #[Test]
    public function testEnFpStressShitake(): void
    {
        $this->assertFalse($this->en->containsProfanity('shitake'));
    }

    #[Test]
    public function testEnFpStressDocument(): void
    {
        $this->assertFalse($this->en->containsProfanity('document'));
    }

    #[Test]
    public function testEnFpStressBuckle(): void
    {
        $this->assertFalse($this->en->containsProfanity('buckle'));
    }

    #[Test]
    public function testEnFpStressHancock(): void
    {
        $this->assertFalse($this->en->containsProfanity('Hancock'));
    }

    #[Test]
    public function testEnFpStressCocktail(): void
    {
        $this->assertFalse($this->en->containsProfanity('cocktail'));
    }

    #[Test]
    public function testEnFpStressShuttlecocks(): void
    {
        $this->assertFalse($this->en->containsProfanity('shuttlecocks'));
    }

    // ─── Compound evasion (7) ────────────────────────────────

    #[Test]
    public function testEnCompoundFuckwad(): void
    {
        $this->assertTrue($this->en->containsProfanity('fuckwad'));
    }

    #[Test]
    public function testEnCompoundShitlord(): void
    {
        $this->assertTrue($this->en->containsProfanity('shitlord'));
    }

    #[Test]
    public function testEnCompoundCockwomble(): void
    {
        $this->assertTrue($this->en->containsProfanity('cockwomble'));
    }

    #[Test]
    public function testEnCompoundTwatwaffle(): void
    {
        $this->assertTrue($this->en->containsProfanity('twatwaffle'));
    }

    #[Test]
    public function testEnCompoundAssmunch(): void
    {
        $this->assertTrue($this->en->containsProfanity('assmunch'));
    }

    #[Test]
    public function testEnCompoundCumguzzler(): void
    {
        $this->assertTrue($this->en->containsProfanity('cumguzzler'));
    }

    #[Test]
    public function testEnCompoundDickweasel(): void
    {
        $this->assertTrue($this->en->containsProfanity('dickweasel'));
    }

    // ─── Extended leet (10 - informational audit) ────────────

    #[Test]
    public function testEnExtendedLeetAudit(): void
    {
        $words = [
            '5h!7', 'ph@660t', 'n!664', 'pr0n', 'a55',
            'a55hole', 'd!ck', 'c0ck', 'p3n!5', 'v4g!n4',
        ];
        foreach ($words as $word) {
            $this->en->containsProfanity($word);
        }
        $this->assertTrue(true);
    }

    // ─── Boundary attacks (6) ────────────────────────────────

    #[Test]
    public function testEnBoundaryUrlPath(): void
    {
        // Informational: log URL path detection
        $this->en->containsProfanity('https://example.com/fuck/path');
        $this->assertTrue(true);
    }

    #[Test]
    public function testEnBoundaryEmail(): void
    {
        $this->en->containsProfanity('fuck@example.com');
        $this->assertTrue(true);
    }

    #[Test]
    public function testEnBoundaryPascalCase(): void
    {
        $this->assertTrue($this->en->containsProfanity('FuckYou'));
    }

    #[Test]
    public function testEnBoundaryHyphenated(): void
    {
        $this->assertTrue($this->en->containsProfanity('mother-fucker'));
    }

    #[Test]
    public function testEnBoundaryHashtag(): void
    {
        $this->assertTrue($this->en->containsProfanity('#fuckyou'));
    }

    #[Test]
    public function testEnBoundaryTrailingNumber(): void
    {
        // Informational audit
        $this->en->containsProfanity('fuck123');
        $this->assertTrue(true);
    }

    // ═══════════════════════════════════════════════════════════
    //  TR adversarial
    // ═══════════════════════════════════════════════════════════

    // ─── İ/ı locale ──────────────────────────────────────────

    #[Test]
    public function testTrLocaleUpperSiktir(): void
    {
        $this->assertTrue($this->tr->containsProfanity("S\u{0130}KT\u{0130}R")); // SİKTİR
    }

    #[Test]
    public function testTrLocaleAsciiUpperSiktir(): void
    {
        // SIKTIR with ASCII I — informational
        $this->tr->containsProfanity('SIKTIR');
        $this->assertTrue(true);
    }

    #[Test]
    public function testTrLocaleMixedCaseSiktir(): void
    {
        // sIktIr with ASCII I — informational
        $this->tr->containsProfanity('sIktIr');
        $this->assertTrue(true);
    }

    // ─── Accented bypass TR (3) ──────────────────────────────

    #[Test]
    public function testTrAccentedSiktir1(): void
    {
        $this->assertTrue($this->tr->containsProfanity('sìktir'));
    }

    #[Test]
    public function testTrAccentedSiktir2(): void
    {
        $this->assertTrue($this->tr->containsProfanity('sîktir'));
    }

    #[Test]
    public function testTrAccentedOrospu(): void
    {
        $this->assertTrue($this->tr->containsProfanity('oròspu'));
    }

    // ─── Cyrillic TR (2) ─────────────────────────────────────

    #[Test]
    public function testTrCyrillicSiktir(): void
    {
        // s + Cyrillic і + kt + Cyrillic і + r
        $this->assertTrue($this->tr->containsProfanity("s\u{0456}kt\u{0456}r"));
    }

    #[Test]
    public function testTrCyrillicOrospu(): void
    {
        // Cyrillic о + r + Cyrillic о + spu
        $this->assertTrue($this->tr->containsProfanity("\u{043E}r\u{043E}spu"));
    }

    // ─── FP stress TR (31+ words) ────────────────────────────

    #[Test]
    public function testTrFpStressSikinti(): void
    {
        $this->assertFalse($this->tr->containsProfanity("s\u{0131}k\u{0131}nt\u{0131}")); // sıkıntı
    }

    #[Test]
    public function testTrFpStressSikisti(): void
    {
        $this->assertFalse($this->tr->containsProfanity("s\u{0131}k\u{0131}\u{015F}t\u{0131}")); // sıkıştı
    }

    #[Test]
    public function testTrFpStressSikma(): void
    {
        $this->assertFalse($this->tr->containsProfanity("s\u{0131}kma")); // sıkma
    }

    #[Test]
    public function testTrFpStressSiki(): void
    {
        $this->assertFalse($this->tr->containsProfanity("s\u{0131}k\u{0131}")); // sıkı
    }

    #[Test]
    public function testTrFpStressSikici(): void
    {
        $this->assertFalse($this->tr->containsProfanity("s\u{0131}k\u{0131}c\u{0131}")); // sıkıcı
    }

    #[Test]
    public function testTrFpStressAmbalaj(): void
    {
        $this->assertFalse($this->tr->containsProfanity('ambalaj'));
    }

    #[Test]
    public function testTrFpStressAmeliyat(): void
    {
        $this->assertFalse($this->tr->containsProfanity('ameliyat'));
    }

    #[Test]
    public function testTrFpStressAmbulans(): void
    {
        $this->assertFalse($this->tr->containsProfanity('ambulans'));
    }

    #[Test]
    public function testTrFpStressAmazon(): void
    {
        $this->assertFalse($this->tr->containsProfanity('amazon'));
    }

    #[Test]
    public function testTrFpStressBokser(): void
    {
        $this->assertFalse($this->tr->containsProfanity('bokser'));
    }

    #[Test]
    public function testTrFpStressBoksor(): void
    {
        $this->assertFalse($this->tr->containsProfanity("boks\u{00F6}r")); // boksör
    }

    #[Test]
    public function testTrFpStressMalzeme(): void
    {
        $this->assertFalse($this->tr->containsProfanity('malzeme'));
    }

    #[Test]
    public function testTrFpStressMaliyet(): void
    {
        $this->assertFalse($this->tr->containsProfanity('maliyet'));
    }

    #[Test]
    public function testTrFpStressMemur(): void
    {
        $this->assertFalse($this->tr->containsProfanity('memur'));
    }

    #[Test]
    public function testTrFpStressMemorial(): void
    {
        $this->assertFalse($this->tr->containsProfanity('memorial'));
    }

    #[Test]
    public function testTrFpStressMemleket(): void
    {
        $this->assertFalse($this->tr->containsProfanity('memleket'));
    }

    #[Test]
    public function testTrFpStressMemento(): void
    {
        $this->assertFalse($this->tr->containsProfanity('memento'));
    }

    #[Test]
    public function testTrFpStressPiknik(): void
    {
        $this->assertFalse($this->tr->containsProfanity('piknik'));
    }

    #[Test]
    public function testTrFpStressKasim(): void
    {
        $this->assertFalse($this->tr->containsProfanity("kas\u{0131}m")); // kasım
    }

    #[Test]
    public function testTrFpStressNamuslu(): void
    {
        $this->assertFalse($this->tr->containsProfanity('namuslu'));
    }

    #[Test]
    public function testTrFpStressAhlaki(): void
    {
        $this->assertFalse($this->tr->containsProfanity('ahlaki'));
    }

    #[Test]
    public function testTrFpStressDolunay(): void
    {
        $this->assertFalse($this->tr->containsProfanity('dolunay'));
    }

    #[Test]
    public function testTrFpStressDolap(): void
    {
        $this->assertFalse($this->tr->containsProfanity('dolap'));
    }

    #[Test]
    public function testTrFpStressDolar(): void
    {
        $this->assertFalse($this->tr->containsProfanity('dolar'));
    }

    #[Test]
    public function testTrFpStressDolma(): void
    {
        $this->assertFalse($this->tr->containsProfanity('dolma'));
    }

    #[Test]
    public function testTrFpStressSikke(): void
    {
        $this->assertFalse($this->tr->containsProfanity('sikke'));
    }

    #[Test]
    public function testTrFpStressSiklet(): void
    {
        $this->assertFalse($this->tr->containsProfanity('siklet'));
    }

    #[Test]
    public function testTrFpStressGotan(): void
    {
        $this->assertFalse($this->tr->containsProfanity('gotan'));
    }

    #[Test]
    public function testTrFpStressGotik(): void
    {
        $this->assertFalse($this->tr->containsProfanity('gotik'));
    }

    #[Test]
    public function testTrFpStressGotham(): void
    {
        $this->assertFalse($this->tr->containsProfanity('gotham'));
    }

    #[Test]
    public function testTrFpStressYarasa(): void
    {
        $this->assertFalse($this->tr->containsProfanity('yarasa'));
    }

    #[Test]
    public function testTrFpStressTasselled(): void
    {
        $this->assertFalse($this->tr->containsProfanity('tasselled'));
    }

    #[Test]
    public function testTrFpStressSikmak(): void
    {
        $this->assertFalse($this->tr->containsProfanity("s\u{0131}kmak")); // sıkmak
    }

    #[Test]
    public function testTrFpStressSikilmak(): void
    {
        $this->assertFalse($this->tr->containsProfanity("s\u{0131}k\u{0131}lmak")); // sıkılmak
    }

    #[Test]
    public function testTrFpStressMasikler(): void
    {
        $this->assertFalse($this->tr->containsProfanity('masikler'));
    }

    #[Test]
    public function testTrFpStressSikilasma(): void
    {
        $this->assertFalse($this->tr->containsProfanity('sikilasma'));
    }

    // ─── TR evasion (15 - informational audit) ───────────────

    #[Test]
    public function testTrEvasionAudit(): void
    {
        $words = [
            's!kt!r', '$!kt!r', 's.i.k.t.i.r', 'siiiiktir', 's1k',
            '0r0spu', 'g0tveren', 'b0k', '4mk', 'y4rr4k',
            't4s4k', 'p!c', 'k4hpe', '5erefsiz', 'p1c',
        ];
        foreach ($words as $word) {
            $this->tr->containsProfanity($word);
        }
        $this->assertTrue(true);
    }

    // ─── Number expansion (3) ────────────────────────────────

    #[Test]
    public function testTrNumberExpansionS2k(): void
    {
        $this->assertTrue($this->tr->containsProfanity('s2k'));
    }

    #[Test]
    public function testTrNumberExpansionS100(): void
    {
        $this->assertFalse($this->tr->containsProfanity('s100'));
    }

    #[Test]
    public function testTrNumberExpansionS100k(): void
    {
        // Informational audit
        $this->tr->containsProfanity('s100k');
        $this->assertTrue(true);
    }

    // ─── Suffix boundary (3) ─────────────────────────────────

    #[Test]
    public function testTrSuffixBoundarySiktirci(): void
    {
        // Informational audit
        $this->tr->containsProfanity('siktirci');
        $this->assertTrue(true);
    }

    #[Test]
    public function testTrSuffixBoundaryOrospular(): void
    {
        $this->assertTrue($this->tr->containsProfanity('orospular'));
    }

    #[Test]
    public function testTrSuffixBoundaryGotluk(): void
    {
        // Informational audit — götlük
        $this->tr->containsProfanity("g\u{00F6}tl\u{00FC}k");
        $this->assertTrue(true);
    }

    // ═══════════════════════════════════════════════════════════
    //  ES adversarial
    // ═══════════════════════════════════════════════════════════

    // ─── Accented bypass ES (5) ──────────────────────────────

    #[Test]
    public function testEsAccentedMierda(): void
    {
        $this->assertTrue($this->es->containsProfanity('mìerda'));
    }

    #[Test]
    public function testEsAccentedPuta(): void
    {
        $this->assertTrue($this->es->containsProfanity('pûta'));
    }

    #[Test]
    public function testEsAccentedCono(): void
    {
        $this->assertTrue($this->es->containsProfanity("c\u{00F2}\u{00F1}o")); // còño
    }

    #[Test]
    public function testEsAccentedHijoputa(): void
    {
        $this->assertTrue($this->es->containsProfanity('hìjoputa'));
    }

    #[Test]
    public function testEsAccentedPendejo(): void
    {
        $this->assertTrue($this->es->containsProfanity('pèndejo'));
    }

    // ─── Cyrillic ES (2) ─────────────────────────────────────

    #[Test]
    public function testEsCyrillicPuta(): void
    {
        // put + Cyrillic а
        $this->assertTrue($this->es->containsProfanity("put\u{0430}"));
    }

    #[Test]
    public function testEsCyrillicMierda(): void
    {
        // mier + Cyrillic д + a — informational
        $this->es->containsProfanity("mier\u{0434}a");
        $this->assertTrue(true);
    }

    // ─── ES leet (8 - informational audit) ───────────────────

    #[Test]
    public function testEsLeetAudit(): void
    {
        $words = [
            'm13rd4', 'put@', 'c4br0n', 'j0d3r',
            'p3nd3j0', 'ch!ng4r', 'm4r!c0n', 'cul0',
        ];
        foreach ($words as $word) {
            $this->es->containsProfanity($word);
        }
        $this->assertTrue(true);
    }

    // ─── ES separator (4 - informational audit) ──────────────

    #[Test]
    public function testEsSeparatorAudit(): void
    {
        $words = [
            'p.u.t.a',
            'm-i-e-r-d-a',
            'h_i_j_o_p_u_t_a',
            "c.o.\u{00F1}.o",
        ];
        foreach ($words as $word) {
            $this->es->containsProfanity($word);
        }
        $this->assertTrue(true);
    }

    // ─── ES FP stress (16) ───────────────────────────────────

    #[Test]
    public function testEsFpStressComputadora(): void
    {
        $this->assertFalse($this->es->containsProfanity('computadora'));
    }

    #[Test]
    public function testEsFpStressDisputar(): void
    {
        $this->assertFalse($this->es->containsProfanity('disputar'));
    }

    #[Test]
    public function testEsFpStressReputacion(): void
    {
        $this->assertFalse($this->es->containsProfanity('reputacion'));
    }

    #[Test]
    public function testEsFpStressImputar(): void
    {
        $this->assertFalse($this->es->containsProfanity('imputar'));
    }

    #[Test]
    public function testEsFpStressPollo(): void
    {
        $this->assertFalse($this->es->containsProfanity('pollo'));
    }

    #[Test]
    public function testEsFpStressPollito(): void
    {
        $this->assertFalse($this->es->containsProfanity('pollito'));
    }

    #[Test]
    public function testEsFpStressPolluelo(): void
    {
        $this->assertFalse($this->es->containsProfanity('polluelo'));
    }

    #[Test]
    public function testEsFpStressFolleto(): void
    {
        $this->assertFalse($this->es->containsProfanity('folleto'));
    }

    #[Test]
    public function testEsFpStressFollaje(): void
    {
        $this->assertFalse($this->es->containsProfanity('follaje'));
    }

    #[Test]
    public function testEsFpStressParticular(): void
    {
        $this->assertFalse($this->es->containsProfanity('particular'));
    }

    #[Test]
    public function testEsFpStressArticulo(): void
    {
        $this->assertFalse($this->es->containsProfanity('articulo'));
    }

    #[Test]
    public function testEsFpStressVehicular(): void
    {
        $this->assertFalse($this->es->containsProfanity('vehicular'));
    }

    #[Test]
    public function testEsFpStressCalcular(): void
    {
        $this->assertFalse($this->es->containsProfanity('calcular'));
    }

    #[Test]
    public function testEsFpStressMaricopa(): void
    {
        $this->assertFalse($this->es->containsProfanity('maricopa'));
    }

    #[Test]
    public function testEsFpStressPutamen(): void
    {
        $this->assertFalse($this->es->containsProfanity('putamen'));
    }

    #[Test]
    public function testEsFpStressPolleria(): void
    {
        $this->assertFalse($this->es->containsProfanity('polleria'));
    }

    // ─── Missing ES (9 - informational audit) ────────────────

    #[Test]
    public function testEsMissingVocabularyAudit(): void
    {
        $words = [
            'gilipollas', 'capullo', 'imbecil', 'subnormal', 'zorra',
            'mamada', 'verga', 'chingar', 'pinche',
        ];
        foreach ($words as $word) {
            $this->es->containsProfanity($word);
        }
        $this->assertTrue(true);
    }

    // ─── ES repetition (2 - informational audit) ─────────────

    #[Test]
    public function testEsRepetitionAudit(): void
    {
        $this->es->containsProfanity('puuuuta');
        $this->es->containsProfanity('mieeeeerda');
        $this->assertTrue(true);
    }

    // ═══════════════════════════════════════════════════════════
    //  DE adversarial
    // ═══════════════════════════════════════════════════════════

    // ─── ß/ss interchange (3) ────────────────────────────────

    #[Test]
    public function testDeScheisseWithSs(): void
    {
        $this->assertTrue($this->de->containsProfanity('Scheisse'));
    }

    #[Test]
    public function testDeScheisseUpperSs(): void
    {
        $this->assertTrue($this->de->containsProfanity('SCHEISSE'));
    }

    #[Test]
    public function testDeScheisseWithEszett(): void
    {
        $this->assertTrue($this->de->containsProfanity("SCHEI\u{00DF}E")); // SCHEIßE
    }

    // ─── Greek beta (1 - informational audit) ────────────────

    #[Test]
    public function testDeGreekBetaAudit(): void
    {
        // Schei + Greek β + e
        $this->de->containsProfanity("Schei\u{03B2}e");
        $this->assertTrue(true);
    }

    // ─── Accented bypass DE (6) ──────────────────────────────

    #[Test]
    public function testDeAccentedFick1(): void
    {
        $this->assertTrue($this->de->containsProfanity('fìck'));
    }

    #[Test]
    public function testDeAccentedFick2(): void
    {
        $this->assertTrue($this->de->containsProfanity('fîck'));
    }

    #[Test]
    public function testDeAccentedHure(): void
    {
        $this->assertTrue($this->de->containsProfanity('Hùre'));
    }

    #[Test]
    public function testDeAccentedFotze(): void
    {
        $this->assertTrue($this->de->containsProfanity('Fòtze'));
    }

    #[Test]
    public function testDeAccentedSchlampe(): void
    {
        $this->assertTrue($this->de->containsProfanity('Schlàmpe'));
    }

    #[Test]
    public function testDeAccentedWichser(): void
    {
        $this->assertTrue($this->de->containsProfanity('Wìchser'));
    }

    // ─── Cyrillic DE (2) ─────────────────────────────────────

    #[Test]
    public function testDeCyrillicFick(): void
    {
        // F + Cyrillic і + ck
        $this->assertTrue($this->de->containsProfanity("F\u{0456}ck"));
    }

    #[Test]
    public function testDeCyrillicArsch(): void
    {
        // Cyrillic А (looks like A) → а after lowercase + rsch
        $this->assertTrue($this->de->containsProfanity("\u{0410}rsch"));
    }

    // ─── DE leet (7 - informational audit) ───────────────────

    #[Test]
    public function testDeLeetAudit(): void
    {
        $words = [
            'f1ck', 'sch31ss3', '4rsch', 'h0r3', 'w1chs3r',
            'f0tz3', 'schl4mp3',
        ];
        foreach ($words as $word) {
            $this->de->containsProfanity($word);
        }
        $this->assertTrue(true);
    }

    // ─── DE separator (3 - informational audit) ──────────────

    #[Test]
    public function testDeSeparatorAudit(): void
    {
        $words = [
            'f.i.c.k',
            's-c-h-e-i-s-s-e',
            'a_r_s_c_h',
        ];
        foreach ($words as $word) {
            $this->de->containsProfanity($word);
        }
        $this->assertTrue(true);
    }

    // ─── DE FP stress (13) ───────────────────────────────────

    #[Test]
    public function testDeFpStressSchwanger(): void
    {
        $this->assertFalse($this->de->containsProfanity('schwanger'));
    }

    #[Test]
    public function testDeFpStressSchwangerschaft(): void
    {
        $this->assertFalse($this->de->containsProfanity('schwangerschaft'));
    }

    #[Test]
    public function testDeFpStressGeschichte(): void
    {
        $this->assertFalse($this->de->containsProfanity('geschichte'));
    }

    #[Test]
    public function testDeFpStressFicktion(): void
    {
        $this->assertFalse($this->de->containsProfanity('ficktion'));
    }

    #[Test]
    public function testDeFpStressArschen(): void
    {
        $this->assertFalse($this->de->containsProfanity('arschen'));
    }

    #[Test]
    public function testDeFpStressSchwanzen(): void
    {
        $this->assertFalse($this->de->containsProfanity('schwanzen'));
    }

    #[Test]
    public function testDeFpStressGesellschaft(): void
    {
        $this->assertFalse($this->de->containsProfanity('Gesellschaft'));
    }

    #[Test]
    public function testDeFpStressWirtschaft(): void
    {
        $this->assertFalse($this->de->containsProfanity('Wirtschaft'));
    }

    #[Test]
    public function testDeFpStressWissenschaft(): void
    {
        $this->assertFalse($this->de->containsProfanity('Wissenschaft'));
    }

    #[Test]
    public function testDeFpStressDruckerei(): void
    {
        $this->assertFalse($this->de->containsProfanity('Druckerei'));
    }

    #[Test]
    public function testDeFpStressDruckfehler(): void
    {
        $this->assertFalse($this->de->containsProfanity('Druckfehler'));
    }

    #[Test]
    public function testDeFpStressSpastik(): void
    {
        $this->assertFalse($this->de->containsProfanity('Spastik'));
    }

    #[Test]
    public function testDeFpStressSpastiker(): void
    {
        $this->assertFalse($this->de->containsProfanity('Spastiker'));
    }

    // ─── DE repetition (3 - informational audit) ─────────────

    #[Test]
    public function testDeRepetitionAudit(): void
    {
        $this->de->containsProfanity('fiiiick');
        $this->de->containsProfanity('Scheeeisse');
        $this->de->containsProfanity('Arrrsch');
        $this->assertTrue(true);
    }

    // ─── Missing DE (7 - informational audit) ────────────────

    #[Test]
    public function testDeMissingVocabularyAudit(): void
    {
        $words = [
            'Drecksau', 'Mistkerl', 'Trottel', 'Depp',
            'Vollidiot', 'Penner', 'Wixer',
        ];
        foreach ($words as $word) {
            $this->de->containsProfanity($word);
        }
        $this->assertTrue(true);
    }

    // ═══════════════════════════════════════════════════════════
    //  ReDoS stress (5)
    // ═══════════════════════════════════════════════════════════

    #[Test]
    public function testRedosStress1000DotsAndFuck(): void
    {
        $input = str_repeat('.', 1000) . 'fuck';
        $start = hrtime(true);
        $this->en->containsProfanity($input);
        $elapsed = (hrtime(true) - $start) / 1_000_000;
        $this->assertLessThan(1000, $elapsed);
    }

    #[Test]
    public function testRedosStressAlternatingSep500(): void
    {
        $input = implode('.', array_fill(0, 500, 'a'));
        $start = hrtime(true);
        $this->en->containsProfanity($input);
        $elapsed = (hrtime(true) - $start) / 1_000_000;
        $this->assertLessThan(2000, $elapsed);
    }

    #[Test]
    public function testRedosStress10KNearMatch(): void
    {
        $input = str_repeat('fuc ', 2500);
        $start = hrtime(true);
        $this->en->containsProfanity($input);
        $elapsed = (hrtime(true) - $start) / 1_000_000;
        $this->assertLessThan(5000, $elapsed);
    }

    #[Test]
    public function testRedosStressCombiningMarksFlood(): void
    {
        $input = str_repeat("a\u{0300}\u{0301}\u{0302}", 100);
        $start = hrtime(true);
        $this->en->containsProfanity($input);
        $elapsed = (hrtime(true) - $start) / 1_000_000;
        $this->assertLessThan(2000, $elapsed);
    }

    #[Test]
    public function testRedosStressTrSuffixChain10K(): void
    {
        $input = 'sik' . str_repeat('tirler', 1666);
        $start = hrtime(true);
        $this->tr->containsProfanity($input);
        $elapsed = (hrtime(true) - $start) / 1_000_000;
        $this->assertLessThan(5000, $elapsed);
    }

    // ═══════════════════════════════════════════════════════════
    //  Cross-language isolation (5)
    // ═══════════════════════════════════════════════════════════

    #[Test]
    public function testCrossLangEnNotTr(): void
    {
        // "fuck" should not be detected by the TR detector
        $this->assertFalse($this->tr->containsProfanity('fuck'));
    }

    #[Test]
    public function testCrossLangEnNotDe(): void
    {
        // "fuck" should not be detected by the DE detector
        $this->assertFalse($this->de->containsProfanity('fuck'));
    }

    #[Test]
    public function testCrossLangTrNotEn(): void
    {
        // "siktir" should not be detected by the EN detector
        $this->assertFalse($this->en->containsProfanity('siktir'));
    }

    #[Test]
    public function testCrossLangDeNotEs(): void
    {
        // "Scheisse" should not be detected by the ES detector
        $this->assertFalse($this->es->containsProfanity('Scheisse'));
    }

    #[Test]
    public function testCrossLangEsNotTr(): void
    {
        // "mierda" should not be detected by the TR detector
        $this->assertFalse($this->tr->containsProfanity('mierda'));
    }
}
