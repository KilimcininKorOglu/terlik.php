<?php

declare(strict_types=1);

use Terlik\Dictionary\Schema;
use Terlik\Lang\LanguageConfig;

$dictionary = json_decode(
    file_get_contents(__DIR__ . '/dictionary.json'),
    true,
    512,
    JSON_THROW_ON_ERROR,
);

$validatedData = Schema::validateDictionary($dictionary);

return new LanguageConfig(
    locale: 'en',

    charMap: [],

    leetMap: [
        '0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a',
        '5' => 's', '6' => 'g', '7' => 't', '8' => 'b',
        '@' => 'a', '$' => 's', '!' => 'i', '#' => 'h',
    ],

    charClasses: [
        'a' => '[a4]',
        'b' => '[b8]',
        'c' => '[c]',
        'd' => '[d]',
        'e' => '[e3]',
        'f' => '[fph]',
        'g' => '[g96]',
        'h' => '[h#]',
        'i' => '[i1]',
        'j' => '[j]',
        'k' => '[k]',
        'l' => '[l1]',
        'm' => '[m]',
        'n' => '[n]',
        'o' => '[o0]',
        'p' => '[p]',
        'q' => '[q]',
        'r' => '[r]',
        's' => '[s5]',
        't' => '[t7]',
        'u' => '[uv]',
        'v' => '[vu]',
        'w' => '[w]',
        'x' => '[x]',
        'y' => '[y]',
        'z' => '[z]',
    ],

    numberExpansions: null,

    dictionary: $validatedData,
);
