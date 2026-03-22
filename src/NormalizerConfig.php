<?php

declare(strict_types=1);

namespace Terlik;

/**
 * Configuration for creating a language-specific normalizer.
 */
final class NormalizerConfig
{
    /**
     * @param string                    $locale           BCP-47 locale tag.
     * @param array<string, string>     $charMap          Language-specific char folding.
     * @param array<string, string>     $leetMap          Leet speak substitution map.
     * @param array<array{0:string,1:string}>|null $numberExpansions Number-to-word expansions.
     */
    public function __construct(
        public readonly string $locale,
        public readonly array $charMap,
        public readonly array $leetMap,
        public readonly ?array $numberExpansions = null,
    ) {}
}
