<?php

declare(strict_types=1);

use Terlik\Dictionary\Schema;
use Terlik\Lang\LanguageConfig;

$dictionaryPath = __DIR__ . '/dictionary.json';
$json = file_get_contents($dictionaryPath);
if ($json === false) {
    throw new \RuntimeException('Failed to read dictionary file: ' . $dictionaryPath);
}
$dictionary = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

$validatedData = Schema::validateDictionary($dictionary);

return new LanguageConfig(
    locale: 'de',

    charMap: [
        'ä' => 'a', 'Ä' => 'a',
        'ö' => 'o', 'Ö' => 'o',
        'ü' => 'u', 'Ü' => 'u',
        'ß' => 'ss',
    ],

    leetMap: [
        '0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a',
        '5' => 's', '7' => 't', '@' => 'a', '$' => 's',
        '!' => 'i',
    ],

    charClasses: [
        'a' => '[a4äÄ]',
        'b' => '[b8]',
        'c' => '[c]',
        'd' => '[d]',
        'e' => '[e3]',
        'f' => '[f]',
        'g' => '[g9]',
        'h' => '[h]',
        'i' => '[i1]',
        'j' => '[j]',
        'k' => '[k]',
        'l' => '[l1]',
        'm' => '[m]',
        'n' => '[n]',
        'o' => '[o0öÖ]',
        'p' => '[p]',
        'q' => '[q]',
        'r' => '[r]',
        's' => '[s5ß]',
        't' => '[t7]',
        'u' => '[uvüÜ]',
        'v' => '[vu]',
        'w' => '[w]',
        'x' => '[x]',
        'y' => '[y]',
        'z' => '[z]',
    ],

    numberExpansions: null,

    dictionary: $validatedData,
);
