<?php

declare(strict_types=1);

namespace Terlik;

/**
 * Profanity severity level.
 */
enum Severity: string
{
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
}

/**
 * Content category for profanity entries.
 */
enum Category: string
{
    case Sexual = 'sexual';
    case Insult = 'insult';
    case Slur = 'slur';
    case General = 'general';
}

/**
 * Detection mode controlling the balance between precision and recall.
 */
enum Mode: string
{
    case Strict = 'strict';
    case Balanced = 'balanced';
    case Loose = 'loose';
}

/**
 * Masking style used when cleaning text.
 */
enum MaskStyle: string
{
    case Stars = 'stars';
    case Partial = 'partial';
    case Replace = 'replace';
}

/**
 * Fuzzy matching algorithm.
 */
enum FuzzyAlgorithm: string
{
    case Levenshtein = 'levenshtein';
    case Dice = 'dice';
}

/**
 * How a match was detected.
 */
enum MatchMethod: string
{
    case Exact = 'exact';
    case Pattern = 'pattern';
    case Fuzzy = 'fuzzy';
}

/**
 * Numeric ordering for severity comparison.
 */
final class SeverityOrder
{
    public const ORDER = [
        'low' => 0,
        'medium' => 1,
        'high' => 2,
    ];

    public static function get(Severity $severity): int
    {
        return self::ORDER[$severity->value];
    }
}

/**
 * A single entry in the profanity dictionary.
 */
final class WordEntry
{
    /**
     * @param string   $root       The canonical root form of the word.
     * @param string[] $variants   Alternative spellings or forms of the root.
     * @param Severity $severity   Severity level of the word.
     * @param Category|null $category Content category.
     * @param bool     $suffixable Whether the suffix engine should match grammatical suffixes.
     */
    public function __construct(
        public readonly string $root,
        public readonly array $variants,
        public readonly Severity $severity,
        public readonly ?Category $category = null,
        public readonly bool $suffixable = false,
    ) {}
}

/**
 * A single profanity match found in the input text.
 */
final class MatchResult
{
    public function __construct(
        public readonly string $word,
        public readonly string $root,
        public readonly int $index,
        public readonly Severity $severity,
        public readonly ?Category $category = null,
        public readonly MatchMethod $method = MatchMethod::Pattern,
    ) {}
}

/**
 * A compiled regex pattern for a dictionary entry.
 */
final class CompiledPattern
{
    /**
     * @param string[] $variants
     */
    public function __construct(
        public readonly string $root,
        public readonly Severity $severity,
        public readonly ?Category $category,
        public readonly string $regex,
        public readonly array $variants,
    ) {}
}

/**
 * Configuration options for creating a Terlik instance.
 */
final class TerlikOptions
{
    /**
     * @param string|null       $language          Language code (default: "tr").
     * @param Mode              $mode              Detection mode (default: balanced).
     * @param MaskStyle         $maskStyle         Masking style (default: stars).
     * @param string[]|null     $customList        Additional words to detect.
     * @param string[]|null     $whitelist         Additional words to exclude from detection.
     * @param bool              $enableFuzzy       Enable fuzzy matching (default: false).
     * @param float             $fuzzyThreshold    Fuzzy similarity threshold 0-1 (default: 0.8).
     * @param FuzzyAlgorithm    $fuzzyAlgorithm    Fuzzy matching algorithm (default: levenshtein).
     * @param int               $maxLength         Maximum input length before truncation (default: 10000).
     * @param string            $replaceMask       Custom mask text for "replace" style (default: "[***]").
     * @param bool              $disableLeetDecode Disable leet-speak decoding (default: false).
     * @param bool              $disableCompound   Disable CamelCase decompounding (default: false).
     * @param Severity|null     $minSeverity       Minimum severity threshold.
     * @param Category[]|null   $excludeCategories Categories to exclude.
     * @param array<string,mixed>|null $extendDictionary External dictionary data to merge.
     */
    public function __construct(
        public readonly ?string $language = null,
        public readonly Mode $mode = Mode::Balanced,
        public readonly MaskStyle $maskStyle = MaskStyle::Stars,
        public readonly ?array $customList = null,
        public readonly ?array $whitelist = null,
        public readonly bool $enableFuzzy = false,
        public readonly float $fuzzyThreshold = 0.8,
        public readonly FuzzyAlgorithm $fuzzyAlgorithm = FuzzyAlgorithm::Levenshtein,
        public readonly int $maxLength = 10000,
        public readonly string $replaceMask = '[***]',
        public readonly bool $disableLeetDecode = false,
        public readonly bool $disableCompound = false,
        public readonly ?Severity $minSeverity = null,
        public readonly ?array $excludeCategories = null,
        public readonly ?array $extendDictionary = null,
    ) {}
}

/**
 * Per-call detection options that override instance defaults.
 */
class DetectOptions
{
    /**
     * @param Mode|null          $mode
     * @param bool|null          $enableFuzzy
     * @param float|null         $fuzzyThreshold
     * @param FuzzyAlgorithm|null $fuzzyAlgorithm
     * @param bool|null          $disableLeetDecode
     * @param bool|null          $disableCompound
     * @param Severity|null      $minSeverity
     * @param Category[]|null    $excludeCategories
     */
    public function __construct(
        public readonly ?Mode $mode = null,
        public readonly ?bool $enableFuzzy = null,
        public readonly ?float $fuzzyThreshold = null,
        public readonly ?FuzzyAlgorithm $fuzzyAlgorithm = null,
        public readonly ?bool $disableLeetDecode = null,
        public readonly ?bool $disableCompound = null,
        public readonly ?Severity $minSeverity = null,
        public readonly ?array $excludeCategories = null,
    ) {}
}

/**
 * Per-call clean options that override instance defaults.
 */
final class CleanOptions extends DetectOptions
{
    public function __construct(
        ?Mode $mode = null,
        ?bool $enableFuzzy = null,
        ?float $fuzzyThreshold = null,
        ?FuzzyAlgorithm $fuzzyAlgorithm = null,
        ?bool $disableLeetDecode = null,
        ?bool $disableCompound = null,
        ?Severity $minSeverity = null,
        ?array $excludeCategories = null,
        public readonly ?MaskStyle $maskStyle = null,
        public readonly ?string $replaceMask = null,
    ) {
        parent::__construct(
            $mode,
            $enableFuzzy,
            $fuzzyThreshold,
            $fuzzyAlgorithm,
            $disableLeetDecode,
            $disableCompound,
            $minSeverity,
            $excludeCategories,
        );
    }
}
