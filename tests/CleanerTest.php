<?php

declare(strict_types=1);

namespace Terlik\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Terlik\Cleaner;
use Terlik\MatchMethod;
use Terlik\MatchResult;
use Terlik\MaskStyle;
use Terlik\Severity;

final class CleanerTest extends TestCase
{
    // ─── applyMask ───────────────────────────────────────────

    #[Test]
    public function testApplyMaskStarsReplacesAllWithAsterisks(): void
    {
        $result = Cleaner::applyMask('siktir', MaskStyle::Stars, '[***]');
        $this->assertSame('******', $result);
    }

    #[Test]
    public function testApplyMaskPartialKeepsFirstAndLastChar(): void
    {
        $result = Cleaner::applyMask('siktir', MaskStyle::Partial, '[***]');
        $this->assertSame('s****r', $result);
    }

    #[Test]
    public function testApplyMaskPartialShortWordTwoChars(): void
    {
        $result = Cleaner::applyMask('am', MaskStyle::Partial, '[***]');
        $this->assertSame('**', $result);
    }

    #[Test]
    public function testApplyMaskPartialShortWordOneChar(): void
    {
        $result = Cleaner::applyMask('x', MaskStyle::Partial, '[***]');
        $this->assertSame('*', $result);
    }

    #[Test]
    public function testApplyMaskReplaceUsesCustomMask(): void
    {
        $result = Cleaner::applyMask('siktir', MaskStyle::Replace, '[küfür]');
        $this->assertSame('[küfür]', $result);
    }

    // ─── cleanText ───────────────────────────────────────────

    #[Test]
    public function testCleanTextWithStarsMask(): void
    {
        $matches = [
            new MatchResult(
                word: 'siktir',
                root: 'sik',
                index: 0,
                severity: Severity::High,
                method: MatchMethod::Pattern,
            ),
        ];
        $result = Cleaner::cleanText('siktir git', $matches, MaskStyle::Stars, '[***]');
        $this->assertSame('****** git', $result);
    }

    #[Test]
    public function testCleanTextWithPartialMask(): void
    {
        $matches = [
            new MatchResult(
                word: 'siktir',
                root: 'sik',
                index: 0,
                severity: Severity::High,
                method: MatchMethod::Pattern,
            ),
        ];
        $result = Cleaner::cleanText('siktir git', $matches, MaskStyle::Partial, '[***]');
        $this->assertSame('s****r git', $result);
    }

    #[Test]
    public function testCleanTextWithReplaceMask(): void
    {
        $matches = [
            new MatchResult(
                word: 'siktir',
                root: 'sik',
                index: 0,
                severity: Severity::High,
                method: MatchMethod::Pattern,
            ),
        ];
        $result = Cleaner::cleanText('siktir git', $matches, MaskStyle::Replace, '[küfür]');
        $this->assertSame('[küfür] git', $result);
    }

    #[Test]
    public function testCleanTextWithMultipleMatches(): void
    {
        $matches = [
            new MatchResult(
                word: 'siktir',
                root: 'sik',
                index: 0,
                severity: Severity::High,
                method: MatchMethod::Pattern,
            ),
            new MatchResult(
                word: 'salak',
                root: 'salak',
                index: 11,
                severity: Severity::Low,
                method: MatchMethod::Pattern,
            ),
        ];
        $result = Cleaner::cleanText('siktir git salak', $matches, MaskStyle::Stars, '[***]');
        $this->assertSame('****** git *****', $result);
    }

    #[Test]
    public function testCleanTextWithEmptyMatchesReturnsOriginal(): void
    {
        $result = Cleaner::cleanText('merhaba dunya', [], MaskStyle::Stars, '[***]');
        $this->assertSame('merhaba dunya', $result);
    }
}
