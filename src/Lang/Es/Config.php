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
    locale: 'es',

    charMap: [
        'ñ' => 'n', 'Ñ' => 'n',
        'á' => 'a', 'Á' => 'a',
        'é' => 'e', 'É' => 'e',
        'í' => 'i', 'Í' => 'i',
        'ó' => 'o', 'Ó' => 'o',
        'ú' => 'u', 'Ú' => 'u',
    ],

    leetMap: [
        '0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a',
        '5' => 's', '7' => 't', '@' => 'a', '$' => 's',
        '!' => 'i',
    ],

    charClasses: [
        'a' => '[a4áÁ]',
        'b' => '[b8]',
        'c' => '[c]',
        'd' => '[d]',
        'e' => '[e3éÉ]',
        'f' => '[f]',
        'g' => '[g9]',
        'h' => '[h]',
        'i' => '[i1íÍ]',
        'j' => '[j]',
        'k' => '[k]',
        'l' => '[l1]',
        'm' => '[m]',
        'n' => '[nñÑ]',
        'o' => '[o0óÓ]',
        'p' => '[p]',
        'q' => '[q]',
        'r' => '[r]',
        's' => '[s5]',
        't' => '[t7]',
        'u' => '[uvúÚ]',
        'v' => '[vu]',
        'w' => '[w]',
        'x' => '[x]',
        'y' => '[y]',
        'z' => '[z]',
    ],

    numberExpansions: null,

    dictionary: $validatedData,
);
