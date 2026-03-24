# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-03-24

### Added
- Synthetic Profanity Dataset Generator (SPDG) ported to PHP with all 4 language datasets

### Changed
- Expanded README with full API reference, strictness controls, and normalization pipeline documentation

### Fixed
- Add file_get_contents return check in language config loaders (prevents misleading JSON errors)
- Use global timeout budget across all patterns in runPatterns (prevents cumulative ReDoS)
- Evict static pattern cache on recompile and add clearPatternCache API
- Handle leading whitespace in mapNormalizedToOriginal offset calculation
- Align strict mode word arrays when tokens normalize to empty
- Use original text for MatchResult word field in Pass 1 (preserves casing for Cleaner)
- Prioritize roots over variants in normalized word lookup
- Validate merged suffix count against MAX_SUFFIXES limit
- Integrate pattern cache eviction with Registry resetCache
- Validate charClasses format before embedding in compiled patterns

## [1.0.0] - 2026-03-23

### Added
- Complete PHP port of terlik.js v2.5.0 profanity detection engine
- Multi-language support: Turkish, English, Spanish, German
- 10-stage text normalization pipeline (invisible chars, NFKD, combining marks, locale-aware lowercase, Cyrillic confusables, char folding, number expansion, leet decode, punctuation removal, repeat collapse)
- 3 detection modes: strict (hash-based), balanced (regex patterns), loose (fuzzy matching)
- Turkish suffix engine with 83 suffixes and up to 2-chain support
- Fuzzy matching algorithms: Levenshtein distance and Dice coefficient
- ReDoS-safe regex patterns with 250ms timeout guard
- Runtime dictionary management (addWords / removeWords)
- Dictionary extension via extendDictionary option
- Mask styles: stars, partial, replace
- Per-call strictness toggles: disableLeetDecode, disableCompound, minSeverity, excludeCategories
- Static warmup() method for multi-language server deployments
- Comprehensive PHPUnit test suite: 21 files, 1271 tests, 3690 assertions
- Full 1:1 test parity with terlik.js
