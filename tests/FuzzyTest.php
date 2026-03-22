<?php

declare(strict_types=1);

namespace Terlik\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Terlik\Fuzzy;
use Terlik\FuzzyAlgorithm;

final class FuzzyTest extends TestCase
{
    // ─── levenshteinDistance ──────────────────────────────────

    #[Test]
    public function testLevenshteinDistanceIdenticalStringsReturnsZero(): void
    {
        $this->assertSame(0, Fuzzy::levenshteinDistance('hello', 'hello'));
    }

    #[Test]
    public function testLevenshteinDistanceSingleEditReturnsOne(): void
    {
        $this->assertSame(1, Fuzzy::levenshteinDistance('cat', 'bat'));
    }

    #[Test]
    public function testLevenshteinDistanceEmptyFirstStringReturnsLengthOfSecond(): void
    {
        $this->assertSame(5, Fuzzy::levenshteinDistance('', 'hello'));
    }

    #[Test]
    public function testLevenshteinDistanceEmptySecondStringReturnsLengthOfFirst(): void
    {
        $this->assertSame(5, Fuzzy::levenshteinDistance('hello', ''));
    }

    #[Test]
    public function testLevenshteinDistanceBothEmptyStringsReturnsZero(): void
    {
        $this->assertSame(0, Fuzzy::levenshteinDistance('', ''));
    }

    #[Test]
    public function testLevenshteinDistanceKittenSittingReturnsThree(): void
    {
        $this->assertSame(3, Fuzzy::levenshteinDistance('kitten', 'sitting'));
    }

    // ─── levenshteinSimilarity ───────────────────────────────

    #[Test]
    public function testLevenshteinSimilarityIdenticalStringsReturnsOne(): void
    {
        $this->assertSame(1.0, Fuzzy::levenshteinSimilarity('hello', 'hello'));
    }

    #[Test]
    public function testLevenshteinSimilarityCompletelyDifferentStringsNearZero(): void
    {
        $result = Fuzzy::levenshteinSimilarity('abc', 'xyz');
        $this->assertEqualsWithDelta(0.0, $result, 0.01);
    }

    #[Test]
    public function testLevenshteinSimilarityPartialMatchBetweenZeroAndOne(): void
    {
        $result = Fuzzy::levenshteinSimilarity('kitten', 'sitting');
        $this->assertGreaterThan(0.0, $result);
        $this->assertLessThan(1.0, $result);
    }

    #[Test]
    public function testLevenshteinSimilarityBothEmptyStringsReturnsOne(): void
    {
        $this->assertSame(1.0, Fuzzy::levenshteinSimilarity('', ''));
    }

    // ─── diceSimilarity ──────────────────────────────────────

    #[Test]
    public function testDiceSimilarityIdenticalStringsReturnsOne(): void
    {
        $this->assertSame(1.0, Fuzzy::diceSimilarity('hello', 'hello'));
    }

    #[Test]
    public function testDiceSimilaritySingleCharStrings(): void
    {
        // Single-char strings: identical returns 1.0
        $this->assertSame(1.0, Fuzzy::diceSimilarity('a', 'a'));
        // Single-char strings: different returns 0.0
        $this->assertSame(0.0, Fuzzy::diceSimilarity('a', 'b'));
    }

    #[Test]
    public function testDiceSimilarityPartialMatchBetweenZeroAndOne(): void
    {
        $result = Fuzzy::diceSimilarity('night', 'nacht');
        $this->assertGreaterThan(0.0, $result);
        $this->assertLessThan(1.0, $result);
    }

    #[Test]
    public function testDiceSimilarityNoSharedBigramsReturnsZero(): void
    {
        $result = Fuzzy::diceSimilarity('ab', 'cd');
        $this->assertSame(0.0, $result);
    }

    // ─── getMatcher ──────────────────────────────────────────

    #[Test]
    public function testGetMatcherReturnsCallableForLevenshtein(): void
    {
        $matcher = Fuzzy::getMatcher(FuzzyAlgorithm::Levenshtein);
        $this->assertIsCallable($matcher);

        $result = $matcher('hello', 'hello');
        $this->assertSame(1.0, $result);
    }

    #[Test]
    public function testGetMatcherReturnsCallableForDice(): void
    {
        $matcher = Fuzzy::getMatcher(FuzzyAlgorithm::Dice);
        $this->assertIsCallable($matcher);

        $result = $matcher('hello', 'hello');
        $this->assertSame(1.0, $result);
    }
}
