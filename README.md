# Terlik.php

Production-ready, multi-language profanity detection engine for PHP. A faithful port of [terlik.js](https://github.com/badursun/terlik.js).

## Features

- 4 languages: Turkish, English, Spanish, German
- 3 detection modes: strict, balanced, loose
- 10-stage text normalization pipeline
- Leet speak decoding, Cyrillic homoglyph detection
- Turkish suffix engine (83 suffixes, deep agglutination)
- Fuzzy matching (Levenshtein / Dice)
- ReDoS-safe regex patterns
- Runtime dictionary management (addWords / removeWords)
- Mask styles: stars, partial, replace

## Requirements

- PHP >= 8.1
- ext-mbstring
- ext-intl

## Installation

```bash
composer require badursun/terlik
```

## Quick Start

```php
use Terlik\Terlik;
use Terlik\TerlikOptions;
use Terlik\Mode;
use Terlik\MaskStyle;

// Turkish (default)
$terlik = new Terlik();
$terlik->containsProfanity('siktir');  // true
$terlik->clean('siktir git');          // "****** git"

// English
$en = new Terlik(new TerlikOptions(language: 'en'));
$en->containsProfanity('fuck');        // true
$en->clean('fuck you');                // "**** you"

// Spanish
$es = new Terlik(new TerlikOptions(language: 'es'));
$es->containsProfanity('mierda');      // true

// German
$de = new Terlik(new TerlikOptions(language: 'de'));
$de->containsProfanity('scheiße');     // true
```

## Configuration

```php
$terlik = new Terlik(new TerlikOptions(
    language: 'tr',              // Language code
    mode: Mode::Balanced,        // strict | balanced | loose
    maskStyle: MaskStyle::Stars, // stars | partial | replace
    replaceMask: '[***]',        // Custom mask for "replace" style
    enableFuzzy: false,          // Enable fuzzy matching
    fuzzyThreshold: 0.8,         // Similarity threshold (0-1)
    maxLength: 10000,            // Max input length
    customList: ['badword'],     // Additional words to detect
    whitelist: ['allowed'],      // Words to exclude
));
```

## Detection Modes

| Mode       | Description                                      |
|------------|--------------------------------------------------|
| `strict`   | Hash-based exact match only                      |
| `balanced` | Full regex pattern matching (default)            |
| `loose`    | Pattern matching + fuzzy similarity              |

## Mask Styles

```php
// Stars: "siktir" -> "******"
$terlik->clean('siktir git');

// Partial: "siktir" -> "s****r"
$partial = new Terlik(new TerlikOptions(maskStyle: MaskStyle::Partial));
$partial->clean('siktir git');

// Replace: "siktir" -> "[CENSORED]"
$replace = new Terlik(new TerlikOptions(
    maskStyle: MaskStyle::Replace,
    replaceMask: '[CENSORED]',
));
$replace->clean('siktir git');
```

## Runtime Dictionary

```php
$terlik = new Terlik();

// Add custom words
$terlik->addWords(['customword']);
$terlik->containsProfanity('customword'); // true

// Remove words
$terlik->removeWords(['customword']);
$terlik->containsProfanity('customword'); // false
```

## Multi-Language Warmup

```php
// Pre-warm instances for multiple languages
$cache = Terlik::warmup(['tr', 'en', 'es', 'de']);
$cache['en']->containsProfanity('fuck'); // true, no cold start
```

## Match Details

```php
$matches = $terlik->getMatches('siktir git');
foreach ($matches as $match) {
    echo $match->word;     // Matched text
    echo $match->root;     // Dictionary root
    echo $match->index;    // Character position
    echo $match->severity; // Severity enum (high/medium/low)
    echo $match->category; // Category enum (sexual/insult/slur/general)
    echo $match->method;   // MatchMethod enum (exact/pattern/fuzzy)
}
```

## Architecture

The library follows a multi-pass detection pipeline:

1. **Pass 0: Normalization** - 10-stage text transformation
2. **Pass 1: Locale-lowered regex matching** - Pattern matching on lowercased text
3. **Pass 2: Normalized text regex matching** - Pattern matching on fully normalized text
4. **Pass 3: CamelCase decompounding** - Breaks camelCase words
5. **Whitelist check** - Final veto mechanism

## License

MIT License - See [LICENSE](LICENSE) for details.

## Credits

PHP port of [terlik.js](https://github.com/badursun/terlik.js) by Anthony Burak DURSUN.
