<?php

declare(strict_types=1);

namespace Terlik\Tests;

use PHPUnit\Framework\TestCase;
use Terlik\Terlik;

/**
 * Comprehensive profanity detection tests for all 39 Turkish profanity roots.
 * Port of profanity.test.ts.
 */
final class ProfanityTest extends TestCase
{
    private static Terlik $terlik;

    public static function setUpBeforeClass(): void
    {
        self::$terlik = new Terlik();
    }

    /**
     * Asserts that the given text is detected as profanity.
     */
    private function detects(string $text, ?string $expectedRoot = null): void
    {
        $result = self::$terlik->containsProfanity($text);
        $this->assertTrue($result, "Expected '{$text}' to be detected as profanity");

        if ($expectedRoot !== null) {
            $matches = self::$terlik->getMatches($text);
            $this->assertNotEmpty($matches, "Expected matches for '{$text}'");
            $roots = array_map(fn($m) => $m->root, $matches);
            $this->assertContains(
                $expectedRoot,
                $roots,
                "Expected root '{$expectedRoot}' in matches for '{$text}', got: " . implode(', ', $roots)
            );
        }
    }

    /**
     * Asserts that the given text is NOT detected as profanity.
     */
    private function clean(string $text): void
    {
        $result = self::$terlik->containsProfanity($text);
        $this->assertFalse($result, "Expected '{$text}' to be clean (not profanity)");
    }

    // ─── 1. sik ────────────────────────────────────────────────

    public function testSikPlain(): void
    {
        $this->detects('sik', 'sik');
    }

    public function testSikSiktir(): void
    {
        $this->detects('siktir', 'sik');
    }

    public function testSikSikerim(): void
    {
        $this->detects('sikerim', 'sik');
    }

    public function testSikSikicem(): void
    {
        $this->detects('sikicem', 'sik');
    }

    public function testSikSiktim(): void
    {
        $this->detects('siktim', 'sik');
    }

    public function testSikSikeyim(): void
    {
        $this->detects('sikeyim', 'sik');
    }

    public function testSikSikis(): void
    {
        $this->detects('sikis', 'sik');
    }

    public function testSikSikik(): void
    {
        $this->detects('sikik', 'sik');
    }

    public function testSikSimVariant(): void
    {
        $this->detects('sikim', 'sik');
    }

    public function testSikUppercase(): void
    {
        $this->detects('SİKTİR', 'sik');
    }

    public function testSikLeet(): void
    {
        $this->detects('$1kt1r', 'sik');
    }

    public function testSikSeparator(): void
    {
        $this->detects('s.i.k', 'sik');
    }

    public function testSikRepetition(): void
    {
        $this->detects('siiiiiktir', 'sik');
    }

    public function testSikInSentence(): void
    {
        $this->detects('hadi siktir git', 'sik');
    }

    public function testSikSuffixSiktiler(): void
    {
        $this->detects('siktiler', 'sik');
    }

    public function testSikSuffixSiktirler(): void
    {
        $this->detects('siktirler', 'sik');
    }

    public function testSikWhitelistSikke(): void
    {
        $this->clean('sikke');
    }

    public function testSikWhitelistSiklet(): void
    {
        $this->clean('siklet');
    }

    // ─── 2. amk ────────────────────────────────────────────────

    public function testAmkPlain(): void
    {
        $this->detects('amk', 'amk');
    }

    public function testAmkAmina(): void
    {
        $this->detects('amina', 'amk');
    }

    public function testAmkAminakoyim(): void
    {
        $this->detects('aminakoyim', 'amk');
    }

    public function testAmkAminakoydugum(): void
    {
        $this->detects('aminakoydugum', 'amk');
    }

    public function testAmkAmq(): void
    {
        $this->detects('amq', 'amk');
    }

    public function testAmkInSentence(): void
    {
        $this->detects('ne diyon amk', 'amk');
    }

    public function testAmkUppercase(): void
    {
        $this->detects('AMK', 'amk');
    }

    // ─── 3. orospu ─────────────────────────────────────────────

    public function testOrospuPlain(): void
    {
        $this->detects('orospu', 'orospu');
    }

    public function testOrospuCocugu(): void
    {
        $this->detects('orospucocugu', 'orospu');
    }

    public function testOrospuOrspu(): void
    {
        $this->detects('orspu', 'orospu');
    }

    public function testOrospuOruspu(): void
    {
        $this->detects('oruspu', 'orospu');
    }

    public function testOrospuOrosbu(): void
    {
        $this->detects('orosbu', 'orospu');
    }

    public function testOrospuInSentence(): void
    {
        $this->detects('bu bir orospu', 'orospu');
    }

    public function testOrospuSuffixOrospuluk(): void
    {
        $this->detects('orospuluk', 'orospu');
    }

    public function testOrospuSuffixOrospular(): void
    {
        $this->detects('orospular', 'orospu');
    }

    public function testOrospuSeparator(): void
    {
        $this->detects('o.r.o.s.p.u', 'orospu');
    }

    // ─── 4. piç ─────────────────────────────────────────────────

    public function testPicPlain(): void
    {
        $this->detects('piç', 'piç');
    }

    public function testPicAscii(): void
    {
        $this->detects('pic', 'piç');
    }

    public function testPicPiclik(): void
    {
        $this->detects('piclik', 'piç');
    }

    public function testPicInSentence(): void
    {
        $this->detects('o bir piç', 'piç');
    }

    public function testPicSuffixPicler(): void
    {
        $this->detects('picler', 'piç');
    }

    public function testPicWhitelistPiknik(): void
    {
        $this->clean('piknik');
    }

    public function testPicWhitelistPikachu(): void
    {
        $this->clean('pikachu');
    }

    // ─── 5. yarrak ──────────────────────────────────────────────

    public function testYarrakPlain(): void
    {
        $this->detects('yarrak', 'yarrak');
    }

    public function testYarrakYarak(): void
    {
        $this->detects('yarak', 'yarrak');
    }

    public function testYarrakYarakli(): void
    {
        $this->detects('yarakli', 'yarrak');
    }

    public function testYarrakDalyarak(): void
    {
        $this->detects('dalyarak', 'yarrak');
    }

    public function testYarrakDalyarrak(): void
    {
        $this->detects('dalyarrak', 'yarrak');
    }

    public function testYarrakInSentence(): void
    {
        $this->detects('ne yarrak gibi', 'yarrak');
    }

    public function testYarrakSuffixYarraklar(): void
    {
        $this->detects('yarraklar', 'yarrak');
    }

    public function testYarrakWhitelistYarasa(): void
    {
        $this->clean('yarasa');
    }

    // ─── 6. göt ─────────────────────────────────────────────────

    public function testGotPlain(): void
    {
        $this->detects('göt', 'göt');
    }

    public function testGotAscii(): void
    {
        $this->detects('got', 'göt');
    }

    public function testGotGotunu(): void
    {
        $this->detects('gotunu', 'göt');
    }

    public function testGotGotlek(): void
    {
        $this->detects('gotlek', 'göt');
    }

    public function testGotGotveren(): void
    {
        $this->detects('gotveren', 'göt');
    }

    public function testGotGotverenler(): void
    {
        $this->detects('gotverenler', 'göt');
    }

    public function testGotInSentence(): void
    {
        $this->detects('senin götün', 'göt');
    }

    public function testGotLeet(): void
    {
        $this->detects('6öt', 'göt');
    }

    public function testGotSuffixGotune(): void
    {
        $this->detects('gotune', 'göt');
    }

    public function testGotSuffixGotler(): void
    {
        $this->detects('gotler', 'göt');
    }

    public function testGotWhitelistGotik(): void
    {
        $this->clean('gotik');
    }

    public function testGotWhitelistGotham(): void
    {
        $this->clean('gotham');
    }

    // ─── 7. am ──────────────────────────────────────────────────

    public function testAmAmcik(): void
    {
        $this->detects('amcik', 'am');
    }

    public function testAmAmcuk(): void
    {
        $this->detects('amcuk', 'am');
    }

    public function testAmInSentence(): void
    {
        $this->detects('senin amcik', 'am');
    }

    public function testAmWhitelistAma(): void
    {
        $this->clean('ama');
    }

    public function testAmWhitelistAmen(): void
    {
        $this->clean('amen');
    }

    public function testAmWhitelistAmir(): void
    {
        $this->clean('amir');
    }

    public function testAmWhitelistAmbalaj(): void
    {
        $this->clean('ambalaj');
    }

    public function testAmWhitelistAmbulans(): void
    {
        $this->clean('ambulans');
    }

    public function testAmWhitelistAmeliyat(): void
    {
        $this->clean('ameliyat');
    }

    public function testAmWhitelistAmerika(): void
    {
        $this->clean('amerika');
    }

    public function testAmWhitelistAmino(): void
    {
        $this->clean('amino');
    }

    public function testAmWhitelistAmonyak(): void
    {
        $this->clean('amonyak');
    }

    public function testAmWhitelistAmpul(): void
    {
        $this->clean('ampul');
    }

    public function testAmAmiVariant(): void
    {
        $this->detects('ami', 'am');
    }

    // ─── 8. taşak ───────────────────────────────────────────────

    public function testTasakPlain(): void
    {
        $this->detects('taşak', 'taşak');
    }

    public function testTasakAscii(): void
    {
        $this->detects('tasak', 'taşak');
    }

    public function testTasakTassak(): void
    {
        $this->detects('tassak', 'taşak');
    }

    public function testTasakTassakli(): void
    {
        $this->detects('tassakli', 'taşak');
    }

    public function testTasakInSentence(): void
    {
        $this->detects('iki tassak', 'taşak');
    }

    public function testTasakSuffixTasaklar(): void
    {
        $this->detects('tasaklar', 'taşak');
    }

    // ─── 9. meme ────────────────────────────────────────────────

    public function testMemePlain(): void
    {
        $this->detects('meme', 'meme');
    }

    public function testMemeInSentence(): void
    {
        $this->detects('büyük meme', 'meme');
    }

    public function testMemeUppercase(): void
    {
        $this->detects('MEME', 'meme');
    }

    public function testMemeWhitelistMemento(): void
    {
        $this->clean('memento');
    }

    public function testMemeWhitelistMemleket(): void
    {
        $this->clean('memleket');
    }

    public function testMemeWhitelistMemur(): void
    {
        $this->clean('memur');
    }

    public function testMemeWhitelistMemorial(): void
    {
        $this->clean('memorial');
    }

    // ─── 10. ibne ───────────────────────────────────────────────

    public function testIbnePlain(): void
    {
        $this->detects('ibne', 'ibne');
    }

    public function testIbneIbneler(): void
    {
        $this->detects('ibneler', 'ibne');
    }

    public function testIbneInSentence(): void
    {
        $this->detects('o bir ibne', 'ibne');
    }

    public function testIbneLeet(): void
    {
        $this->detects('i8ne', 'ibne');
    }

    public function testIbneSuffixIbnelik(): void
    {
        $this->detects('ibnelik', 'ibne');
    }

    public function testIbneSuffixIbneler(): void
    {
        $this->detects('ibneler', 'ibne');
    }

    // ─── 11. gavat ──────────────────────────────────────────────

    public function testGavatPlain(): void
    {
        $this->detects('gavat', 'gavat');
    }

    public function testGavatGavatlik(): void
    {
        $this->detects('gavatlik', 'gavat');
    }

    public function testGavatInSentence(): void
    {
        $this->detects('o bir gavat', 'gavat');
    }

    public function testGavatSuffixGavatlar(): void
    {
        $this->detects('gavatlar', 'gavat');
    }

    public function testGavatUppercase(): void
    {
        $this->detects('GAVAT', 'gavat');
    }

    // ─── 12. pezevenk ───────────────────────────────────────────

    public function testPezevenkPlain(): void
    {
        $this->detects('pezevenk', 'pezevenk');
    }

    public function testPezevenkPezo(): void
    {
        $this->detects('pezo', 'pezevenk');
    }

    public function testPezevenkInSentence(): void
    {
        $this->detects('o bir pezevenk', 'pezevenk');
    }

    public function testPezevenkSuffixPezevenkler(): void
    {
        $this->detects('pezevenkler', 'pezevenk');
    }

    public function testPezevenkSuffixPezevenklik(): void
    {
        $this->detects('pezevenklik', 'pezevenk');
    }

    // ─── 13. bok ────────────────────────────────────────────────

    public function testBokPlain(): void
    {
        $this->detects('bok', 'bok');
    }

    public function testBokBoktan(): void
    {
        $this->detects('boktan', 'bok');
    }

    public function testBokInSentence(): void
    {
        $this->detects('ne bok yiyorsun', 'bok');
    }

    public function testBokLeet(): void
    {
        $this->detects('8ok', 'bok');
    }

    public function testBokSuffixBoklar(): void
    {
        $this->detects('boklar', 'bok');
    }

    public function testBokSuffixBoklu(): void
    {
        $this->detects('boklu', 'bok');
    }

    public function testBokWhitelistBokser(): void
    {
        $this->clean('bokser');
    }

    public function testBokWhitelistBoksor(): void
    {
        $this->clean('boksör');
    }

    // ─── 14. haysiyetsiz ────────────────────────────────────────

    public function testHaysiyetsizPlain(): void
    {
        $this->detects('haysiyetsiz', 'haysiyetsiz');
    }

    public function testHaysiyetsizInSentence(): void
    {
        $this->detects('sen haysiyetsiz birisin', 'haysiyetsiz');
    }

    public function testHaysiyetsizUppercase(): void
    {
        $this->detects('HAYSIYETSIZ', 'haysiyetsiz');
    }

    // ─── 15. salak ──────────────────────────────────────────────

    public function testSalakPlain(): void
    {
        $this->detects('salak', 'salak');
    }

    public function testSalakSalaklik(): void
    {
        $this->detects('salaklik', 'salak');
    }

    public function testSalakInSentence(): void
    {
        $this->detects('ne salak adamsın', 'salak');
    }

    public function testSalakUppercase(): void
    {
        $this->detects('SALAK', 'salak');
    }

    public function testSalakSuffixSalaksin(): void
    {
        $this->detects('salaksin', 'salak');
    }

    public function testSalakSuffixSalaklar(): void
    {
        $this->detects('salaklar', 'salak');
    }

    // ─── 16. aptal ──────────────────────────────────────────────

    public function testAptalPlain(): void
    {
        $this->detects('aptal', 'aptal');
    }

    public function testAptalAptallik(): void
    {
        $this->detects('aptallik', 'aptal');
    }

    public function testAptalAptalca(): void
    {
        $this->detects('aptalca', 'aptal');
    }

    public function testAptalInSentence(): void
    {
        $this->detects('ne aptal bir soru', 'aptal');
    }

    public function testAptalLeet(): void
    {
        $this->detects('@pt@l', 'aptal');
    }

    public function testAptalSuffixAptallar(): void
    {
        $this->detects('aptallar', 'aptal');
    }

    public function testAptalSuffixAptallarin(): void
    {
        $this->detects('aptallarin', 'aptal');
    }

    // ─── 17. gerizekalı ─────────────────────────────────────────

    public function testGerizekaliPlain(): void
    {
        $this->detects('gerizekalı', 'gerizekalı');
    }

    public function testGerizekaliAscii(): void
    {
        $this->detects('gerizekali', 'gerizekalı');
    }

    public function testGerizekaliInSentence(): void
    {
        $this->detects('tam bir gerizekali', 'gerizekalı');
    }

    public function testGerizekaliSuffixGerizekaliler(): void
    {
        $this->detects('gerizekaliler', 'gerizekalı');
    }

    // ─── 18. mal ────────────────────────────────────────────────

    public function testMalPlain(): void
    {
        $this->detects('mal', 'mal');
    }

    public function testMalInSentence(): void
    {
        $this->detects('bu adam mal', 'mal');
    }

    public function testMalUppercase(): void
    {
        $this->detects('MAL', 'mal');
    }

    public function testMalWhitelistMalzeme(): void
    {
        $this->clean('malzeme');
    }

    public function testMalWhitelistMaliyet(): void
    {
        $this->clean('maliyet');
    }

    public function testMalWhitelistMalik(): void
    {
        $this->clean('malik');
    }

    public function testMalWhitelistMalikane(): void
    {
        $this->clean('malikane');
    }

    public function testMalWhitelistMaliye(): void
    {
        $this->clean('maliye');
    }

    public function testMalWhitelistMalta(): void
    {
        $this->clean('malta');
    }

    public function testMalWhitelistMalt(): void
    {
        $this->clean('malt');
    }

    public function testMalWhitelistMallorca(): void
    {
        $this->clean('mallorca');
    }

    // ─── 19. dangalak ───────────────────────────────────────────

    public function testDangalakPlain(): void
    {
        $this->detects('dangalak', 'dangalak');
    }

    public function testDangalakInSentence(): void
    {
        $this->detects('ne dangalak adam', 'dangalak');
    }

    public function testDangalakSuffixDangalaklar(): void
    {
        $this->detects('dangalaklar', 'dangalak');
    }

    public function testDangalakUppercase(): void
    {
        $this->detects('DANGALAK', 'dangalak');
    }

    // ─── 20. ezik ───────────────────────────────────────────────

    public function testEzikPlain(): void
    {
        $this->detects('ezik', 'ezik');
    }

    public function testEzikInSentence(): void
    {
        $this->detects('tam bir ezik', 'ezik');
    }

    public function testEzikSuffixEzikler(): void
    {
        $this->detects('ezikler', 'ezik');
    }

    public function testEzikSuffixEziklik(): void
    {
        $this->detects('eziklik', 'ezik');
    }

    public function testEzikUppercase(): void
    {
        $this->detects('EZIK', 'ezik');
    }

    // ─── 21. puşt ───────────────────────────────────────────────

    public function testPustPlain(): void
    {
        $this->detects('puşt', 'puşt');
    }

    public function testPustAscii(): void
    {
        $this->detects('pust', 'puşt');
    }

    public function testPustPustt(): void
    {
        $this->detects('pustt', 'puşt');
    }

    public function testPustInSentence(): void
    {
        $this->detects('o bir pust', 'puşt');
    }

    public function testPustLeet(): void
    {
        $this->detects('pu$t', 'puşt');
    }

    public function testPustSuffixPustlar(): void
    {
        $this->detects('pustlar', 'puşt');
    }

    public function testPustSuffixPustluk(): void
    {
        $this->detects('pustluk', 'puşt');
    }

    // ─── 22. şerefsiz ──────────────────────────────────────────

    public function testSerefsizPlain(): void
    {
        $this->detects('şerefsiz', 'şerefsiz');
    }

    public function testSerefsizAscii(): void
    {
        $this->detects('serefsiz', 'şerefsiz');
    }

    public function testSerefsizSerefsizler(): void
    {
        $this->detects('serefsizler', 'şerefsiz');
    }

    public function testSerefsizInSentence(): void
    {
        $this->detects('o bir serefsiz', 'şerefsiz');
    }

    public function testSerefsizSuffixSerefsizlik(): void
    {
        $this->detects('serefsizlik', 'şerefsiz');
    }

    public function testSerefsizUppercase(): void
    {
        $this->detects('SEREFSIZ', 'şerefsiz');
    }

    // ─── 23. yavşak ────────────────────────────────────────────

    public function testYavsakPlain(): void
    {
        $this->detects('yavşak', 'yavşak');
    }

    public function testYavsakAscii(): void
    {
        $this->detects('yavsak', 'yavşak');
    }

    public function testYavsakInSentence(): void
    {
        $this->detects('ne yavsak adam', 'yavşak');
    }

    public function testYavsakSuffixYavsaklik(): void
    {
        $this->detects('yavsaklik', 'yavşak');
    }

    public function testYavsakSuffixYavsaklar(): void
    {
        $this->detects('yavsaklar', 'yavşak');
    }

    public function testYavsakUppercase(): void
    {
        $this->detects('YAVSAK', 'yavşak');
    }

    // ─── 24. döl ────────────────────────────────────────────────

    public function testDolPlain(): void
    {
        $this->detects('döl', 'döl');
    }

    public function testDolAscii(): void
    {
        $this->detects('dol', 'döl');
    }

    public function testDolDolunu(): void
    {
        $this->detects('dolunu', 'döl');
    }

    public function testDolInSentence(): void
    {
        $this->detects('senin döl', 'döl');
    }

    public function testDolDolcu(): void
    {
        $this->detects('dolcu', 'döl');
    }

    public function testDolWhitelistDolunay(): void
    {
        $this->clean('dolunay');
    }

    public function testDolWhitelistDolum(): void
    {
        $this->clean('dolum');
    }

    public function testDolWhitelistDoluluk(): void
    {
        $this->clean('doluluk');
    }

    public function testDolWhitelistDolmen(): void
    {
        $this->clean('dolmen');
    }

    // ─── 25. kahpe ──────────────────────────────────────────────

    public function testKahpePlain(): void
    {
        $this->detects('kahpe', 'kahpe');
    }

    public function testKahpeKahpelik(): void
    {
        $this->detects('kahpelik', 'kahpe');
    }

    public function testKahpeInSentence(): void
    {
        $this->detects('o bir kahpe', 'kahpe');
    }

    public function testKahpeSuffixKahpeler(): void
    {
        $this->detects('kahpeler', 'kahpe');
    }

    public function testKahpeSuffixKahpelikler(): void
    {
        $this->detects('kahpelikler', 'kahpe');
    }

    public function testKahpeUppercase(): void
    {
        $this->detects('KAHPE', 'kahpe');
    }

    // ─── 26. sürtük ─────────────────────────────────────────────

    public function testSurtukPlain(): void
    {
        $this->detects('sürtük', 'sürtük');
    }

    public function testSurtukAscii(): void
    {
        $this->detects('surtuk', 'sürtük');
    }

    public function testSurtukInSentence(): void
    {
        $this->detects('o bir surtuk', 'sürtük');
    }

    public function testSurtukSuffixSurtukler(): void
    {
        $this->detects('surtukler', 'sürtük');
    }

    public function testSurtukSuffixSurtukluk(): void
    {
        $this->detects('surtukluk', 'sürtük');
    }

    public function testSurtukUppercase(): void
    {
        $this->detects('SÜRTÜK', 'sürtük');
    }

    // ─── 27. kaltak ─────────────────────────────────────────────

    public function testKaltakPlain(): void
    {
        $this->detects('kaltak', 'kaltak');
    }

    public function testKaltakInSentence(): void
    {
        $this->detects('o bir kaltak', 'kaltak');
    }

    public function testKaltakSuffixKaltaklar(): void
    {
        $this->detects('kaltaklar', 'kaltak');
    }

    public function testKaltakSuffixKaltaklik(): void
    {
        $this->detects('kaltaklik', 'kaltak');
    }

    public function testKaltakUppercase(): void
    {
        $this->detects('KALTAK', 'kaltak');
    }

    // ─── 28. fahişe ─────────────────────────────────────────────

    public function testFahisePlain(): void
    {
        $this->detects('fahişe', 'fahişe');
    }

    public function testFahiseAscii(): void
    {
        $this->detects('fahise', 'fahişe');
    }

    public function testFahiseInSentence(): void
    {
        $this->detects('o bir fahise', 'fahişe');
    }

    public function testFahiseSuffixFahiseler(): void
    {
        $this->detects('fahiseler', 'fahişe');
    }

    public function testFahiseSuffixFahiselik(): void
    {
        $this->detects('fahiselik', 'fahişe');
    }

    public function testFahiseUppercase(): void
    {
        $this->detects('FAHISE', 'fahişe');
    }

    // ─── 29. kevaşe ─────────────────────────────────────────────

    public function testKevasePlain(): void
    {
        $this->detects('kevaşe', 'kevaşe');
    }

    public function testKevaseAscii(): void
    {
        $this->detects('kevase', 'kevaşe');
    }

    public function testKevaseInSentence(): void
    {
        $this->detects('o bir kevase', 'kevaşe');
    }

    public function testKevaseSuffixKevaseler(): void
    {
        $this->detects('kevaseler', 'kevaşe');
    }

    public function testKevaseUppercase(): void
    {
        $this->detects('KEVASE', 'kevaşe');
    }

    // ─── 30. oğlancı ────────────────────────────────────────────

    public function testOglanciPlain(): void
    {
        $this->detects('oğlancı', 'oğlancı');
    }

    public function testOglanciAscii(): void
    {
        $this->detects('oglanci', 'oğlancı');
    }

    public function testOglanciInSentence(): void
    {
        $this->detects('o bir oglanci', 'oğlancı');
    }

    public function testOglanciSuffixOglancilar(): void
    {
        $this->detects('oglancilar', 'oğlancı');
    }

    public function testOglanciSuffixOglancilik(): void
    {
        $this->detects('oglancilik', 'oğlancı');
    }

    public function testOglanciUppercase(): void
    {
        $this->detects('OGLANCI', 'oğlancı');
    }

    // ─── 31. dingil ─────────────────────────────────────────────

    public function testDingilPlain(): void
    {
        $this->detects('dingil', 'dingil');
    }

    public function testDingilInSentence(): void
    {
        $this->detects('ne dingil adam', 'dingil');
    }

    public function testDingilSuffixDingiller(): void
    {
        $this->detects('dingiller', 'dingil');
    }

    public function testDingilSuffixDingillik(): void
    {
        $this->detects('dingillik', 'dingil');
    }

    public function testDingilUppercase(): void
    {
        $this->detects('DINGIL', 'dingil');
    }

    // ─── 32. avanak ─────────────────────────────────────────────

    public function testAvanakPlain(): void
    {
        $this->detects('avanak', 'avanak');
    }

    public function testAvanakInSentence(): void
    {
        $this->detects('ne avanak adam', 'avanak');
    }

    public function testAvanakSuffixAvanaklar(): void
    {
        $this->detects('avanaklar', 'avanak');
    }

    public function testAvanakSuffixAvanaklik(): void
    {
        $this->detects('avanaklik', 'avanak');
    }

    public function testAvanakUppercase(): void
    {
        $this->detects('AVANAK', 'avanak');
    }

    // ─── 33. manyak ─────────────────────────────────────────────

    public function testManyakPlain(): void
    {
        $this->detects('manyak', 'manyak');
    }

    public function testManyakInSentence(): void
    {
        $this->detects('ne manyak adam', 'manyak');
    }

    public function testManyakSuffixManyaklar(): void
    {
        $this->detects('manyaklar', 'manyak');
    }

    public function testManyakSuffixManyaklik(): void
    {
        $this->detects('manyaklik', 'manyak');
    }

    public function testManyakUppercase(): void
    {
        $this->detects('MANYAK', 'manyak');
    }

    // ─── 34. hödük ──────────────────────────────────────────────

    public function testHodukPlain(): void
    {
        $this->detects('hödük', 'hödük');
    }

    public function testHodukAscii(): void
    {
        $this->detects('hoduk', 'hödük');
    }

    public function testHodukInSentence(): void
    {
        $this->detects('ne hoduk adam', 'hödük');
    }

    public function testHodukSuffixHodukler(): void
    {
        $this->detects('hodukler', 'hödük');
    }

    public function testHodukSuffixHodukluk(): void
    {
        $this->detects('hodukluk', 'hödük');
    }

    public function testHodukUppercase(): void
    {
        $this->detects('HODUK', 'hödük');
    }

    // ─── 35. kepaze ─────────────────────────────────────────────

    public function testKepazePlain(): void
    {
        $this->detects('kepaze', 'kepaze');
    }

    public function testKepazeInSentence(): void
    {
        $this->detects('ne kepaze adam', 'kepaze');
    }

    public function testKepazeSuffixKepazeler(): void
    {
        $this->detects('kepazeler', 'kepaze');
    }

    public function testKepazeSuffixKepazelik(): void
    {
        $this->detects('kepazelik', 'kepaze');
    }

    public function testKepazeUppercase(): void
    {
        $this->detects('KEPAZE', 'kepaze');
    }

    // ─── 36. rezil ──────────────────────────────────────────────

    public function testRezilPlain(): void
    {
        $this->detects('rezil', 'rezil');
    }

    public function testRezilInSentence(): void
    {
        $this->detects('ne rezil adam', 'rezil');
    }

    public function testRezilSuffixReziller(): void
    {
        $this->detects('reziller', 'rezil');
    }

    public function testRezilSuffixRezillik(): void
    {
        $this->detects('rezillik', 'rezil');
    }

    public function testRezilUppercase(): void
    {
        $this->detects('REZIL', 'rezil');
    }

    // ─── 37. kalleş ─────────────────────────────────────────────

    public function testKallesPlain(): void
    {
        $this->detects('kalleş', 'kalleş');
    }

    public function testKallesAscii(): void
    {
        $this->detects('kalles', 'kalleş');
    }

    public function testKallesInSentence(): void
    {
        $this->detects('o bir kalles', 'kalleş');
    }

    public function testKallesSuffixKallesler(): void
    {
        $this->detects('kallesler', 'kalleş');
    }

    public function testKallesSuffixKalleslik(): void
    {
        $this->detects('kalleslik', 'kalleş');
    }

    public function testKallesUppercase(): void
    {
        $this->detects('KALLES', 'kalleş');
    }

    // ─── 38. namussuz ───────────────────────────────────────────

    public function testNamussuzPlain(): void
    {
        $this->detects('namussuz', 'namussuz');
    }

    public function testNamussuzInSentence(): void
    {
        $this->detects('o bir namussuz', 'namussuz');
    }

    public function testNamussuzSuffixNamussuzlar(): void
    {
        $this->detects('namussuzlar', 'namussuz');
    }

    public function testNamussuzSuffixNamussuzluk(): void
    {
        $this->detects('namussuzluk', 'namussuz');
    }

    public function testNamussuzUppercase(): void
    {
        $this->detects('NAMUSSUZ', 'namussuz');
    }

    public function testNamussuzWhitelistNamus(): void
    {
        $this->clean('namus');
    }

    public function testNamussuzWhitelistNamuslu(): void
    {
        $this->clean('namuslu');
    }

    // ─── 39. ahlaksız ───────────────────────────────────────────

    public function testAhlaksizPlain(): void
    {
        $this->detects('ahlaksız', 'ahlaksız');
    }

    public function testAhlaksizAscii(): void
    {
        $this->detects('ahlaksiz', 'ahlaksız');
    }

    public function testAhlaksizInSentence(): void
    {
        $this->detects('o bir ahlaksiz', 'ahlaksız');
    }

    public function testAhlaksizSuffixAhlaksizlar(): void
    {
        $this->detects('ahlaksizlar', 'ahlaksız');
    }

    public function testAhlaksizSuffixAhlaksizlik(): void
    {
        $this->detects('ahlaksizlik', 'ahlaksız');
    }

    public function testAhlaksizUppercase(): void
    {
        $this->detects('AHLAKSIZ', 'ahlaksız');
    }

    public function testAhlaksizWhitelistAhlak(): void
    {
        $this->clean('ahlak');
    }

    public function testAhlaksizWhitelistAhlaki(): void
    {
        $this->clean('ahlaki');
    }
}
