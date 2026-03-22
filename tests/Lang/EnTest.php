<?php

declare(strict_types=1);

namespace Terlik\Tests\Lang;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Terlik\Terlik;
use Terlik\TerlikOptions;

final class EnTest extends TestCase
{
    private static ?Terlik $terlik = null;

    private static function terlik(): Terlik
    {
        return self::$terlik ??= new Terlik(new TerlikOptions(language: 'en'));
    }

    // ──────────────────────────────────────────────────────────
    //  Root detection (51 roots)
    // ──────────────────────────────────────────────────────────

    /** @return array<string, array{string}> */
    public static function rootProvider(): array
    {
        return [
            'fuck'       => ['fuck'],
            'shit'       => ['shit'],
            'bitch'      => ['bitch'],
            'damn'       => ['damn'],
            'asshole'    => ['asshole'],
            'dick'       => ['dick'],
            'cock'       => ['cock'],
            'cunt'       => ['cunt'],
            'whore'      => ['whore'],
            'slut'       => ['slut'],
            'piss'       => ['piss'],
            'wank'       => ['wank'],
            'twat'       => ['twat'],
            'bollocks'   => ['bollocks'],
            'crap'       => ['crap'],
            'retard'     => ['retard'],
            'faggot'     => ['faggot'],
            'douche'     => ['douche'],
            'spic'       => ['spic'],
            'kike'       => ['kike'],
            'chink'      => ['chink'],
            'gook'       => ['gook'],
            'tranny'     => ['tranny'],
            'dyke'       => ['dyke'],
            'coon'       => ['coon'],
            'wetback'    => ['wetback'],
            'bellend'    => ['bellend'],
            'skank'      => ['skank'],
            'scumbag'    => ['scumbag'],
            'turd'       => ['turd'],
            'bugger'     => ['bugger'],
            'hell'       => ['hell'],
            'prick'      => ['prick'],
            'screw'      => ['screw'],
            'porn'       => ['porn'],
            'blowjob'    => ['blowjob'],
            'jizz'       => ['jizz'],
            'dildo'      => ['dildo'],
            'orgasm'     => ['orgasm'],
            'orgy'       => ['orgy'],
            'hooker'     => ['hooker'],
            'negro'      => ['negro'],
            'masturbate' => ['masturbate'],
            'semen'      => ['semen'],
            'pussy'      => ['pussy'],
            'cum'        => ['cum'],
            'penis'      => ['penis'],
            'tit'        => ['tit'],
            'vagina'     => ['vagina'],
            'anal'       => ['anal'],
            'rape'       => ['rape'],
        ];
    }

    #[Test]
    #[DataProvider('rootProvider')]
    public function rootDetection(string $word): void
    {
        $this->assertTrue(
            self::terlik()->containsProfanity($word),
            "Root '{$word}' should be detected as profanity"
        );
    }

    // ──────────────────────────────────────────────────────────
    //  Variant detection (117 variants)
    // ──────────────────────────────────────────────────────────

    /** @return array<string, array{string}> */
    public static function variantProvider(): array
    {
        return [
            'fucking'        => ['fucking'],
            'fucker'         => ['fucker'],
            'motherfucker'   => ['motherfucker'],
            'stfu'           => ['stfu'],
            'fuckboy'        => ['fuckboy'],
            'fucktard'       => ['fucktard'],
            'fuckhead'       => ['fuckhead'],
            'wtf'            => ['wtf'],
            'mofo'           => ['mofo'],
            'unfucking'      => ['unfucking'],
            'fuckery'        => ['fuckery'],
            'shitty'         => ['shitty'],
            'bullshit'       => ['bullshit'],
            'dipshit'        => ['dipshit'],
            'shithole'       => ['shithole'],
            'shitbag'        => ['shitbag'],
            'shitload'       => ['shitload'],
            'shithouse'      => ['shithouse'],
            'shitlist'       => ['shitlist'],
            'shitfaced'      => ['shitfaced'],
            'bitchy'         => ['bitchy'],
            'bitching'       => ['bitching'],
            'bitchslap'      => ['bitchslap'],
            'cocksucker'     => ['cocksucker'],
            'cocksucking'    => ['cocksucking'],
            'cockblock'      => ['cockblock'],
            'slutty'         => ['slutty'],
            'whorish'        => ['whorish'],
            'pissed'         => ['pissed'],
            'pissing'        => ['pissing'],
            'wanker'         => ['wanker'],
            'wanking'        => ['wanking'],
            'retarded'       => ['retarded'],
            'nigga'          => ['nigga'],
            'fag'            => ['fag'],
            'fags'           => ['fags'],
            'douchebag'      => ['douchebag'],
            'dickhead'       => ['dickhead'],
            'dickwad'        => ['dickwad'],
            'jackass'        => ['jackass'],
            'dumbass'        => ['dumbass'],
            'smartass'       => ['smartass'],
            'asscrack'       => ['asscrack'],
            'assclown'       => ['assclown'],
            'goddamn'        => ['goddamn'],
            'spicks'         => ['spicks'],
            'kikes'          => ['kikes'],
            'chinks'         => ['chinks'],
            'chinky'         => ['chinky'],
            'gooks'          => ['gooks'],
            'trannies'       => ['trannies'],
            'dykes'          => ['dykes'],
            'coons'          => ['coons'],
            'wetbacks'       => ['wetbacks'],
            'bellends'       => ['bellends'],
            'skanky'         => ['skanky'],
            'scumbags'       => ['scumbags'],
            'turds'          => ['turds'],
            'buggered'       => ['buggered'],
            'buggering'      => ['buggering'],
            'buggery'        => ['buggery'],
            'hells'          => ['hells'],
            'pricks'         => ['pricks'],
            'pricked'        => ['pricked'],
            'pricking'       => ['pricking'],
            'screwed'        => ['screwed'],
            'screwing'       => ['screwing'],
            'screws'         => ['screws'],
            'pornographic'   => ['pornographic'],
            'pornography'    => ['pornography'],
            'porno'          => ['porno'],
            'blowjobs'       => ['blowjobs'],
            'jizzed'         => ['jizzed'],
            'jizzing'        => ['jizzing'],
            'dildos'         => ['dildos'],
            'orgasms'        => ['orgasms'],
            'orgasmic'       => ['orgasmic'],
            'orgies'         => ['orgies'],
            'hookers'        => ['hookers'],
            'negroes'        => ['negroes'],
            'masturbating'   => ['masturbating'],
            'masturbation'   => ['masturbation'],
            'pussies'        => ['pussies'],
            'cumming'        => ['cumming'],
            'cumshot'        => ['cumshot'],
            'penises'        => ['penises'],
            'tits'           => ['tits'],
            'titty'          => ['titty'],
            'titties'        => ['titties'],
            'vaginas'        => ['vaginas'],
            'vaginal'        => ['vaginal'],
            'raped'          => ['raped'],
            'raping'         => ['raping'],
            'rapist'         => ['rapist'],
            'rapists'        => ['rapists'],
            'fuckyou'        => ['fuckyou'],
            'fuckoff'        => ['fuckoff'],
            'fuckwad'        => ['fuckwad'],
            'fuckup'         => ['fuckup'],
            'fuckall'        => ['fuckall'],
            'shitlord'       => ['shitlord'],
            'shitstain'      => ['shitstain'],
            'shitbrain'      => ['shitbrain'],
            'cockwomble'     => ['cockwomble'],
            'twatwaffle'     => ['twatwaffle'],
            'assmunch'       => ['assmunch'],
            'cumguzzler'     => ['cumguzzler'],
            'cumdumpster'    => ['cumdumpster'],
            'dickweasel'     => ['dickweasel'],
            'thundercunt'    => ['thundercunt'],
        ];
    }

    #[Test]
    #[DataProvider('variantProvider')]
    public function variantDetection(string $word): void
    {
        $this->assertTrue(
            self::terlik()->containsProfanity($word),
            "Variant '{$word}' should be detected as profanity"
        );
    }

    // ──────────────────────────────────────────────────────────
    //  Evasion detection
    // ──────────────────────────────────────────────────────────

    /** @return array<string, array{string}> */
    public static function evasionProvider(): array
    {
        return [
            'separator: f.u.c.k'        => ['f.u.c.k'],
            'leet: fck'                  => ['fck'],
            'repetition: fuuuck'         => ['fuuuck'],
            'leet: $h1t'                 => ['$h1t'],
            'leet: b1tch'                => ['b1tch'],
            'leet: phuck'                => ['phuck'],
            'leet: phucking'             => ['phucking'],
            'leet: s#it'                 => ['s#it'],
            'leet: 8itch'                => ['8itch'],
            'leet: ni66er'               => ['ni66er'],
            'leet: n!66er'               => ['n!66er'],
            'camelCase: FuckYou'         => ['FuckYou'],
            'camelCase: ShitHead'        => ['ShitHead'],
            'mixed case: SHITlord'       => ['SHITlord'],
            'hashtag: #fuckyou'          => ['#fuckyou'],
            'accented: fück'             => ['fück'],
            'cyrillic с: fuсk'           => ["fu\u{0441}k"],
            'fullwidth: ｆｕｃｋ'        => ["\u{FF46}\u{FF55}\u{FF43}\u{FF4B}"],
        ];
    }

    #[Test]
    #[DataProvider('evasionProvider')]
    public function evasionDetection(string $word): void
    {
        $this->assertTrue(
            self::terlik()->containsProfanity($word),
            "Evasion '{$word}' should be detected as profanity"
        );
    }

    // ──────────────────────────────────────────────────────────
    //  Whitelist — safe words that must NOT trigger detection
    // ──────────────────────────────────────────────────────────

    /** @return array<string, array{string}> */
    public static function whitelistProvider(): array
    {
        return [
            'assassin'         => ['assassin'],
            'assassinate'      => ['assassinate'],
            'assistant'        => ['assistant'],
            'assessment'       => ['assessment'],
            'class'            => ['class'],
            'classic'          => ['classic'],
            'classify'         => ['classify'],
            'classroom'        => ['classroom'],
            'grass'            => ['grass'],
            'grasshopper'      => ['grasshopper'],
            'mass'             => ['mass'],
            'massive'          => ['massive'],
            'pass'             => ['pass'],
            'passage'          => ['passage'],
            'passenger'        => ['passenger'],
            'passion'          => ['passion'],
            'passive'          => ['passive'],
            'passport'         => ['passport'],
            'assume'           => ['assume'],
            'asset'            => ['asset'],
            'assess'           => ['assess'],
            'dickens'          => ['dickens'],
            'cocktail'         => ['cocktail'],
            'cockatoo'         => ['cockatoo'],
            'cockatiel'        => ['cockatiel'],
            'cockpit'          => ['cockpit'],
            'cockroach'        => ['cockroach'],
            'cockney'          => ['cockney'],
            'peacock'          => ['peacock'],
            'shuttlecock'      => ['shuttlecock'],
            'woodcock'         => ['woodcock'],
            'scrap'            => ['scrap'],
            'piston'           => ['piston'],
            'bassist'          => ['bassist'],
            'embassy'          => ['embassy'],
            'hassle'           => ['hassle'],
            'massage'          => ['massage'],
            'compass'          => ['compass'],
            'harass'           => ['harass'],
            'shiitake'         => ['shiitake'],
            'cocoon'           => ['cocoon'],
            'raccoon'          => ['raccoon'],
            'tycoon'           => ['tycoon'],
            'dike'             => ['dike'],
            'vandyke'          => ['vandyke'],
            'scunthorpe'       => ['scunthorpe'],
            'cocked'           => ['cocked'],
            'hello'            => ['hello'],
            'shell'            => ['shell'],
            'seashell'         => ['seashell'],
            'eggshell'         => ['eggshell'],
            'nutshell'         => ['nutshell'],
            'bombshell'        => ['bombshell'],
            'helium'           => ['helium'],
            'helicopter'       => ['helicopter'],
            'helmet'           => ['helmet'],
            'prickle'          => ['prickle'],
            'prickly'          => ['prickly'],
            'screwdriver'      => ['screwdriver'],
            'corkscrew'        => ['corkscrew'],
            'puck'             => ['puck'],
            'pucks'            => ['pucks'],
            'pussycat'         => ['pussycat'],
            'pussywillow'      => ['pussywillow'],
            'pussyfoot'        => ['pussyfoot'],
            'penistone'        => ['penistone'],
            'analysis'         => ['analysis'],
            'analyst'          => ['analyst'],
            'analog'           => ['analog'],
            'analogy'          => ['analogy'],
            'analytical'       => ['analytical'],
            'analyze'          => ['analyze'],
            'grape'            => ['grape'],
            'drape'            => ['drape'],
            'scrape'           => ['scrape'],
            'rapeseed'         => ['rapeseed'],
            'therapist'        => ['therapist'],
            'therapy'          => ['therapy'],
            'title'            => ['title'],
            'titan'            => ['titan'],
            'titillate'        => ['titillate'],
            'JavaScript'       => ['JavaScript'],
            'CockpitDoor'      => ['CockpitDoor'],
            'AssessmentReport' => ['AssessmentReport'],
        ];
    }

    #[Test]
    #[DataProvider('whitelistProvider')]
    public function whitelistSafeWords(string $word): void
    {
        $this->assertFalse(
            self::terlik()->containsProfanity($word),
            "Safe word '{$word}' must NOT be detected as profanity"
        );
    }

    // ──────────────────────────────────────────────────────────
    //  Masking
    // ──────────────────────────────────────────────────────────

    #[Test]
    public function masksDetectedWordsInSentence(): void
    {
        $result = self::terlik()->clean('what the fuck');

        $this->assertStringNotContainsString('fuck', strtolower($result));
        $this->assertStringContainsString('*', $result);
    }

    // ──────────────────────────────────────────────────────────
    //  Isolation — must NOT detect other languages' profanity
    // ──────────────────────────────────────────────────────────

    #[Test]
    public function doesNotDetectTurkishProfanity(): void
    {
        $this->assertFalse(self::terlik()->containsProfanity('siktir'));
    }

    #[Test]
    public function doesNotDetectSpanishProfanity(): void
    {
        $this->assertFalse(self::terlik()->containsProfanity('mierda'));
    }

    #[Test]
    public function doesNotDetectGermanProfanity(): void
    {
        $this->assertFalse(self::terlik()->containsProfanity('scheiße'));
    }
}
