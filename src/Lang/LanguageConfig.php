<?php

declare(strict_types=1);

namespace Terlik\Lang;

/**
 * Language configuration containing dictionary, charMap, leetMap, and charClasses.
 */
final class LanguageConfig
{
    /**
     * @param string                              $locale           BCP-47 locale tag.
     * @param array<string, string>               $charMap          Diacritics normalization map.
     * @param array<string, string>               $leetMap          Leet speak substitution map.
     * @param array<string, string>               $charClasses      Visual similarity regex character classes.
     * @param array<array{0:string,1:string}>|null $numberExpansions Number-to-word expansions.
     * @param array<string, mixed>                $dictionary       Validated dictionary data.
     */
    public function __construct(
        public readonly string $locale,
        public readonly array $charMap,
        public readonly array $leetMap,
        public readonly array $charClasses,
        public readonly ?array $numberExpansions,
        public readonly array $dictionary,
    ) {}
}
