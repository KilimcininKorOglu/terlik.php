<?php

declare(strict_types=1);

namespace Terlik\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Terlik\Mode;
use Terlik\Terlik;
use Terlik\TerlikCore;
use Terlik\TerlikOptions;

final class LazyCompilationTest extends TestCase
{
    // ─── construction performance ───────────────────────────────

    #[Test]
    public function testConstructsQuickly(): void
    {
        $start = hrtime(true);
        $terlik = new Terlik();
        $elapsed = (hrtime(true) - $start) / 1_000_000;

        $this->assertLessThan(50, $elapsed, 'Construction should complete in under 50ms');
    }

    // ─── transparent lazy compilation ───────────────────────────

    #[Test]
    public function testDetectInBalancedModeReturnsCorrectResults(): void
    {
        $terlik = new Terlik(new TerlikOptions(mode: Mode::Balanced));

        $this->assertTrue($terlik->containsProfanity('siktir git'));
        $this->assertFalse($terlik->containsProfanity('merhaba'));
    }

    #[Test]
    public function testGetMatchesTriggersCompilationAndReturnsDetails(): void
    {
        $terlik = new Terlik();
        $matches = $terlik->getMatches('siktir git');

        $this->assertNotEmpty($matches);

        $first = $matches[0];
        $this->assertNotEmpty($first->word);
        $this->assertNotEmpty($first->root);
        $this->assertIsInt($first->index);
    }

    #[Test]
    public function testCleanTriggersCompilationAndMasksProfanity(): void
    {
        $terlik = new Terlik();
        $result = $terlik->clean('siktir git');

        $this->assertStringNotContainsString('siktir', $result);
        $this->assertStringContainsString('*', $result);
    }

    // ─── strict mode ────────────────────────────────────────────

    #[Test]
    public function testStrictDetectUsesHashLookup(): void
    {
        $start = hrtime(true);
        $terlik = new Terlik(new TerlikOptions(mode: Mode::Strict));
        $constructTime = (hrtime(true) - $start) / 1_000_000;

        $detectStart = hrtime(true);
        $result = $terlik->containsProfanity('siktir');
        $detectTime = (hrtime(true) - $detectStart) / 1_000_000;

        $this->assertLessThan(50, $constructTime, 'Strict mode construction should be fast');
        $this->assertLessThan(50, $detectTime, 'Strict mode detection should be fast');
    }

    #[Test]
    public function testStrictModeStillDetectsCorrectly(): void
    {
        $terlik = new Terlik(new TerlikOptions(mode: Mode::Strict));

        $this->assertTrue($terlik->containsProfanity('siktir'));
        $this->assertFalse($terlik->containsProfanity('merhaba'));
    }

    // ─── getPatterns triggers compilation ───────────────────────

    #[Test]
    public function testGetPatternsTriggersCompilationAndReturnsPatterns(): void
    {
        $terlik = new Terlik();
        $patterns = $terlik->getPatterns();

        $this->assertIsArray($patterns);
        $this->assertNotEmpty($patterns);

        foreach ($patterns as $key => $regex) {
            $this->assertIsString($key, 'Pattern key should be a string');
            $this->assertIsString($regex, 'Pattern regex should be a string');
        }
    }

    // ─── recompile after addWords/removeWords ───────────────────

    #[Test]
    public function testAddWordsTriggersRecompileAndDetectsNewWord(): void
    {
        $terlik = new Terlik();

        $this->assertFalse($terlik->containsProfanity('xyzfoobar'));

        $terlik->addWords(['xyzfoobar']);
        $this->assertTrue($terlik->containsProfanity('xyzfoobar'));
    }

    #[Test]
    public function testRemoveWordsTriggersRecompileAndStopsDetecting(): void
    {
        $terlik = new Terlik();

        $this->assertTrue($terlik->containsProfanity('salak'));

        $terlik->removeWords(['salak']);
        $this->assertFalse($terlik->containsProfanity('salak'));
    }

    // ─── warmup static method ───────────────────────────────────

    #[Test]
    public function testWarmupCreatesInstancesThatWorkCorrectly(): void
    {
        $instances = Terlik::warmup(['tr']);

        $this->assertArrayHasKey('tr', $instances);
        $this->assertInstanceOf(Terlik::class, $instances['tr']);

        $trInstance = $instances['tr'];
        $this->assertTrue($trInstance->containsProfanity('siktir'));
        $this->assertFalse($trInstance->containsProfanity('merhaba'));
    }
}
