# Terlik.php

Production-ready, multi-language profanity detection engine for PHP. A faithful port of [terlik.js](https://github.com/badursun/terlik.js).

## Features

- **4 languages** -- Turkish, English, Spanish, German
- **3 detection modes** -- strict (hash-based), balanced (regex), loose (fuzzy)
- **10-stage normalization pipeline** -- invisible chars, NFKD, combining marks, locale-aware lowercase, Cyrillic confusables, char folding, number expansion, leet decode, punctuation removal, repeat collapse
- **Evasion resistance** -- leet speak (`$1kt1r`), separators (`s.i.k.t.i.r`), repetition (`fuuuck`), Cyrillic homoglyphs, fullwidth characters, accented bypasses
- **Turkish suffix engine** -- 83 grammatical suffixes with up to 2-chain support
- **Fuzzy matching** -- Levenshtein distance and Dice coefficient algorithms
- **ReDoS-safe** -- bounded separators `{0,3}`, 250ms per-pattern timeout, max pattern length guard
- **Runtime dictionary** -- `addWords()` / `removeWords()` with automatic recompilation
- **Dictionary extension** -- merge custom dictionaries via `extendDictionary` option
- **Mask styles** -- stars (`******`), partial (`s****r`), replace (`[CENSORED]`)
- **Per-call overrides** -- `minSeverity`, `excludeCategories`, `disableLeetDecode`, `disableCompound`
- **Zero runtime dependencies** -- only ext-mbstring and ext-intl

## Requirements

- PHP >= 8.1
- ext-mbstring
- ext-intl

## Installation

```bash
composer require KilimcininKorOglu/terlik.php
```

## Quick Start

```php
use Terlik\Terlik;
use Terlik\TerlikOptions;

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

## API Reference

### Constructor

```php
$terlik = new Terlik(new TerlikOptions(
    language: 'tr',                    // Language code: tr, en, es, de (default: tr)
    mode: Mode::Balanced,              // Detection mode (default: balanced)
    maskStyle: MaskStyle::Stars,       // Mask style (default: stars)
    replaceMask: '[***]',              // Custom mask for "replace" style
    enableFuzzy: false,                // Enable fuzzy matching (default: false)
    fuzzyThreshold: 0.8,               // Similarity threshold 0-1 (default: 0.8)
    fuzzyAlgorithm: FuzzyAlgorithm::Levenshtein,  // levenshtein or dice
    maxLength: 10000,                  // Max input length before truncation
    customList: ['badword'],           // Additional words to detect
    whitelist: ['allowed'],            // Words to exclude from detection
    extendDictionary: [...],           // External dictionary to merge
    disableLeetDecode: false,          // Disable leet-speak normalization
    disableCompound: false,            // Disable CamelCase decompounding
    minSeverity: Severity::Low,        // Minimum severity threshold
    excludeCategories: [Category::Sexual],  // Categories to exclude
));
```

### Methods

| Method                                 | Returns         | Description                          |
|----------------------------------------|-----------------|--------------------------------------|
| `containsProfanity(string, ?options)`  | `bool`          | Check if text contains profanity     |
| `getMatches(string, ?options)`         | `MatchResult[]` | Get detailed match information       |
| `clean(string, ?options)`              | `string`        | Replace profanity with masks         |
| `addWords(string[])`                   | `void`          | Add words to dictionary at runtime   |
| `removeWords(string[])`                | `void`          | Remove words from dictionary         |
| `getPatterns()`                        | `array`         | Get compiled regex patterns          |
| `Terlik::warmup(?languages, ?options)` | `array`         | Pre-warm multiple language instances |

### MatchResult

```php
$matches = $terlik->getMatches('siktir git');
foreach ($matches as $match) {
    $match->word;     // string  -- Matched text from original input
    $match->root;     // string  -- Dictionary root word
    $match->index;    // int     -- Character position in original text
    $match->severity; // Severity enum -- high, medium, low
    $match->category; // ?Category enum -- sexual, insult, slur, general
    $match->method;   // MatchMethod enum -- exact, pattern, fuzzy
}
```

## Detection Modes

| Mode       | Description                                        | Use Case               |
|------------|----------------------------------------------------|------------------------|
| `strict`   | Hash-based exact match after normalization         | Lowest false positives |
| `balanced` | Full regex pattern matching with evasion detection | Default, recommended   |
| `loose`    | Pattern matching + fuzzy similarity matching       | Maximum recall         |

```php
use Terlik\Mode;

// Strict: won't catch "s.i.k.t.i.r" or leet speak
$strict = new Terlik(new TerlikOptions(mode: Mode::Strict));

// Balanced (default): catches separators, leet, repetition
$balanced = new Terlik(new TerlikOptions(mode: Mode::Balanced));

// Loose: also catches misspellings via fuzzy matching
$loose = new Terlik(new TerlikOptions(mode: Mode::Loose));
```

## Mask Styles

```php
use Terlik\MaskStyle;

// Stars: "siktir" -> "******"
$terlik = new Terlik();
$terlik->clean('siktir git');  // "****** git"

// Partial: "siktir" -> "s****r"
$partial = new Terlik(new TerlikOptions(maskStyle: MaskStyle::Partial));
$partial->clean('siktir git');  // "s****r git"

// Replace: "siktir" -> "[CENSORED]"
$replace = new Terlik(new TerlikOptions(
    maskStyle: MaskStyle::Replace,
    replaceMask: '[CENSORED]',
));
$replace->clean('siktir git');  // "[CENSORED] git"
```

## Strictness Controls

### Severity Filtering

```php
use Terlik\Severity;

// Only detect high severity (skip low and medium)
$terlik = new Terlik(new TerlikOptions(minSeverity: Severity::High));
$terlik->containsProfanity('salak');   // false (low severity)
$terlik->containsProfanity('siktir');  // true  (high severity)
```

### Category Exclusion

```php
use Terlik\Category;

// Exclude sexual category
$terlik = new Terlik(new TerlikOptions(
    excludeCategories: [Category::Sexual],
));
$terlik->containsProfanity('siktir');  // false (sexual, excluded)
$terlik->containsProfanity('orospu');  // true  (insult, kept)
```

### Disable Leet Decode

```php
// Disable leet-speak normalization (safety layers like NFKD still active)
$terlik = new Terlik(new TerlikOptions(disableLeetDecode: true));
$terlik->containsProfanity('$1kt1r');  // false (leet not decoded)
$terlik->containsProfanity('siktir');  // true  (plain text still detected)
```

### Disable CamelCase Decompounding

```php
$en = new Terlik(new TerlikOptions(language: 'en', disableCompound: true));
$en->containsProfanity('ShitPerson');    // false (compound not split)
$en->containsProfanity('motherfucker');  // true  (explicit variant)
```

### Per-Call Overrides

All strictness controls can be overridden per-call:

```php
use Terlik\DetectOptions;
use Terlik\CleanOptions;

$terlik = new Terlik();  // default: detect everything

// Override for a single call
$terlik->containsProfanity('salak', new DetectOptions(
    minSeverity: Severity::High,
));  // false (low severity filtered)

$terlik->containsProfanity('salak');  // true (default still works)

// Clean with per-call override
$terlik->clean('salak siktir', new CleanOptions(
    minSeverity: Severity::High,
));  // "salak ****** " (only high severity masked)
```

## Dictionary Extension

Merge custom dictionaries with built-in ones:

```php
$terlik = new Terlik(new TerlikOptions(
    extendDictionary: [
        'version' => 1,
        'suffixes' => ['ler', 'lar'],
        'entries' => [
            [
                'root' => 'customword',
                'variants' => ['cust0mw0rd'],
                'severity' => 'high',
                'category' => 'general',
                'suffixable' => true,
            ],
        ],
        'whitelist' => ['safeword'],
    ],
));

$terlik->containsProfanity('customword');     // true
$terlik->containsProfanity('customwordler');  // true (suffix)
$terlik->containsProfanity('siktir');         // true (built-in still works)
```

## Runtime Dictionary

```php
$terlik = new Terlik();

// Add custom words (auto-recompiles patterns)
$terlik->addWords(['customword']);
$terlik->containsProfanity('customword');  // true

// Remove words (auto-recompiles patterns)
$terlik->removeWords(['customword']);
$terlik->containsProfanity('customword');  // false
```

## Multi-Language Warmup

Pre-warm instances for server deployments to eliminate cold-start latency:

```php
$cache = Terlik::warmup(['tr', 'en', 'es', 'de']);
$cache['tr']->containsProfanity('siktir');  // true, no cold start
$cache['en']->containsProfanity('fuck');    // true, no cold start
```

## Architecture

The library follows a multi-pass detection pipeline:

1. **Pass 1: Locale-lowered regex** -- Pattern matching on locale-aware lowercased text
2. **Pass 2: Normalized text regex** -- Pattern matching on fully normalized text (10-stage pipeline)
3. **Pass 3: CamelCase decompounding** -- Splits compound words (`ShitPerson` -> `Shit Person`)
4. **Pass 4: Fuzzy matching** -- Levenshtein/Dice similarity (loose mode only)
5. **Whitelist veto** -- Each pass checks whitelist before accepting matches

### Normalization Pipeline (10 stages)

| Stage | Operation              | Example                        |
|-------|------------------------|--------------------------------|
| 1     | Strip invisible chars  | `f\u200Buck` -> `fuck`         |
| 2     | NFKD decomposition     | fullwidth chars -> ASCII       |
| 3     | Strip combining marks  | accented chars -> base letters |
| 4     | Locale-aware lowercase | `SiKTiR` -> `siktir`           |
| 5     | Cyrillic confusables   | Cyrillic `а` -> Latin `a`      |
| 6     | Language char folding  | `ç` -> `c`, `ß` -> `ss`        |
| 7     | Number expansion       | `s2k` -> `sikik` (TR only)     |
| 8     | Leet decode            | `$1kt1r` -> `siktir`           |
| 9     | Punctuation removal    | `s.i.k` -> `sik`               |
| 10    | Repeat collapse        | `siiiktir` -> `siktir`         |

## Testing

```bash
# Full test suite: 21 files, 1271 tests, 3690 assertions
php vendor/bin/phpunit

# With test names
php vendor/bin/phpunit --testdox

# Single file
php vendor/bin/phpunit tests/EdgeCasesTest.php

# Single test
php vendor/bin/phpunit --filter testFalsePositiveSikke
```

## Language Support

| Language | Roots | Variants | Suffixes | Whitelist |
|----------|-------|----------|----------|-----------|
| Turkish  | 39    | 150+     | 83       | 150+      |
| English  | 51    | 117+     | --       | 82+       |
| Spanish  | 28    | 46+      | --       | 12+       |
| German   | 28    | 32+      | --       | 3+        |

## License

MIT License - See [LICENSE](LICENSE) for details.

## Credits

PHP port of [terlik.js](https://github.com/badursun/terlik.js) by Anthony Burak DURSUN.
