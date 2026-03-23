#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Synthetic Profanity Dataset Generator (PHP port)
 * Generates JSONL datasets for profanity detection testing.
 */

// ============================================================================
// 1. mulberry32 — Deterministic PRNG
// ============================================================================
function mulberry32(int $seed): Closure
{
    $s = $seed | 0;

    return static function () use (&$s): float {
        $s = ($s + 0x6D2B79F5) & 0xFFFFFFFF;
        // Use float math to emulate JS Math.imul behavior
        $t = $s ^ ($s >> 15);
        $t = (int) (fmod((float) $t * (float) (1 | $s), 4294967296.0));
        $t = ($t + (int) (fmod((float) ($t ^ ($t >> 7)) * (float) (61 | $t), 4294967296.0))) ^ $t;
        $result = ($t ^ ($t >> 14)) & 0xFFFFFFFF;

        return $result / 4294967296.0;
    };
}

// ============================================================================
// 2. CLI Argument Parser
// ============================================================================
function parseArgs(array $argv): array
{
    $args = array_slice($argv, 1);
    $opts = [
        'lang' => null,
        'pos' => 20000,
        'neg' => 20000,
        'seed' => 42,
        'data' => __DIR__ . '/data',
        'out' => __DIR__ . '/output',
        'format' => 'jsonl',
        'stats' => false,
        'difficulty' => 'all',
        'validate' => false,
        'dryRun' => false,
    ];

    $langShortcuts = ['tr', 'en', 'es', 'de'];

    for ($i = 0, $len = count($args); $i < $len; $i++) {
        $arg = $args[$i];
        if ($arg === '--lang' && isset($args[$i + 1])) {
            $opts['lang'] = $args[++$i];
        } elseif ($arg === '--pos' && isset($args[$i + 1])) {
            $opts['pos'] = (int) $args[++$i];
        } elseif ($arg === '--neg' && isset($args[$i + 1])) {
            $opts['neg'] = (int) $args[++$i];
        } elseif ($arg === '--seed' && isset($args[$i + 1])) {
            $opts['seed'] = (int) $args[++$i];
        } elseif ($arg === '--data' && isset($args[$i + 1])) {
            $opts['data'] = realpath($args[++$i]) ?: $args[$i];
        } elseif ($arg === '--out' && isset($args[$i + 1])) {
            $opts['out'] = realpath($args[++$i]) ?: $args[$i];
        } elseif ($arg === '--format' && isset($args[$i + 1])) {
            $opts['format'] = $args[++$i];
        } elseif ($arg === '--difficulty' && isset($args[$i + 1])) {
            $opts['difficulty'] = $args[++$i];
        } elseif ($arg === '--stats') {
            $opts['stats'] = true;
        } elseif ($arg === '--validate') {
            $opts['validate'] = true;
        } elseif ($arg === '--dry-run') {
            $opts['dryRun'] = true;
        } else {
            $shortcut = ltrim($arg, '-');
            if (in_array($shortcut, $langShortcuts, true)) {
                $opts['lang'] = $shortcut;
            }
        }
    }

    if ($opts['lang'] === null) {
        fwrite(STDERR, "ERROR: Language required. Usage: --lang tr or --tr\n");
        fwrite(STDERR, "Supported languages: tr, en, es, de\n");
        exit(1);
    }

    if (!in_array($opts['lang'], $langShortcuts, true)) {
        fwrite(STDERR, "ERROR: Unsupported language: {$opts['lang']}\n");
        exit(1);
    }

    if (!in_array($opts['format'], ['jsonl', 'csv', 'both'], true)) {
        fwrite(STDERR, "ERROR: Invalid format: {$opts['format']}. Use jsonl, csv, or both.\n");
        exit(1);
    }

    return $opts;
}

// ============================================================================
// 3. LANG_CONFIG
// ============================================================================
$langConfig = [
    'tr' => ['name' => 'Turkce', 'locale' => 'tr', 'vowels' => 'aeıioöuü'],
    'en' => ['name' => 'English', 'locale' => 'en', 'vowels' => 'aeiou'],
    'es' => ['name' => 'Espanol', 'locale' => 'es', 'vowels' => 'aeiou'],
    'de' => ['name' => 'Deutsch', 'locale' => 'de', 'vowels' => 'aeiouäöü'],
];

$currentLang = 'tr';

// ============================================================================
// 4. File Loaders
// ============================================================================
function parseUnicodeEscapes(string $str): string
{
    return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', static function (array $m): string {
        return mb_chr((int) hexdec($m[1]));
    }, $str) ?? $str;
}

function loadTextFile(string $filePath): array
{
    if (!file_exists($filePath)) {
        fwrite(STDERR, "WARNING: File not found: {$filePath}\n");

        return [];
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return [];
    }

    return array_values(array_filter(
        array_map('trim', $lines),
        static fn(string $l) => $l !== '' && !str_starts_with($l, '#'),
    ));
}

function loadMapFile(string $filePath): array
{
    $lines = loadTextFile($filePath);
    $map = [];
    foreach ($lines as $line) {
        if (preg_match('/^(.+?)\s*->\s*(.+)$/', $line, $match)) {
            $key = trim($match[1]);
            $vals = array_map(static fn(string $v) => parseUnicodeEscapes(trim($v)), explode(',', $match[2]));
            $map[$key] = $vals;
        }
    }

    return $map;
}

function loadUnicodeListFile(string $filePath): array
{
    return array_map('parseUnicodeEscapes', loadTextFile($filePath));
}

// ============================================================================
// 5. loadAllData
// ============================================================================
function loadAllData(string $dataDir, string $lang): array
{
    $langDir = $dataDir . '/' . $lang;
    $sharedDir = $dataDir . '/shared';

    $data = [
        'rootsPositive' => loadTextFile($langDir . '/roots_positive.txt'),
        'rootsNegative' => loadTextFile($langDir . '/roots_negative.txt'),
        'templatesPositive' => loadTextFile($langDir . '/templates_positive.txt'),
        'templatesNegative' => loadTextFile($langDir . '/templates_negative.txt'),
        'contextsPositive' => loadTextFile($langDir . '/contexts_positive.txt'),
        'contextsNegative' => loadTextFile($langDir . '/contexts_negative.txt'),
        'suffixes' => loadTextFile($langDir . '/suffixes.txt'),
        'leetMap' => loadMapFile($langDir . '/leet_map.txt'),
        'emojiReplacements' => loadTextFile($langDir . '/emoji_replacements.txt'),
        'separators' => loadTextFile($sharedDir . '/separators.txt'),
        'unicodeMap' => loadMapFile($sharedDir . '/unicode_map.txt'),
        'zalgoChars' => loadUnicodeListFile($sharedDir . '/zalgo_chars.txt'),
        'zwcChars' => loadUnicodeListFile($sharedDir . '/zwc_chars.txt'),
    ];

    $missing = [];
    if (empty($data['rootsPositive'])) {
        $missing[] = 'roots_positive.txt';
    }
    if (empty($data['rootsNegative'])) {
        $missing[] = 'roots_negative.txt';
    }
    if (empty($data['templatesPositive'])) {
        $missing[] = 'templates_positive.txt';
    }
    if (empty($data['templatesNegative'])) {
        $missing[] = 'templates_negative.txt';
    }

    if (!empty($missing)) {
        fwrite(STDERR, "ERROR: Required files empty or missing [{$lang}]: " . implode(', ', $missing) . "\n");
        exit(1);
    }

    return $data;
}

// ============================================================================
// 6. Transform Functions (13 transforms)
// ============================================================================
function transformSuffix(string $word, array $data, Closure $rand): string
{
    if (empty($data['suffixes'])) {
        return $word;
    }
    $suffix = $data['suffixes'][(int) floor($rand() * count($data['suffixes']))];

    return $word . $suffix;
}

function transformCharRepeat(string $word, array $data, Closure $rand): string
{
    $chars = mb_str_split($word);
    if (count($chars) < 2) {
        return $word;
    }
    $idx = (int) floor($rand() * count($chars));
    $times = 2 + (int) floor($rand() * 3);
    $chars[$idx] = str_repeat($chars[$idx], $times);

    return implode('', $chars);
}

function transformLeet(string $word, array $data, Closure $rand): string
{
    $leetMap = $data['leetMap'];
    if (empty($leetMap)) {
        return $word;
    }
    $result = '';
    foreach (mb_str_split($word) as $ch) {
        $lower = mb_strtolower($ch);
        if (isset($leetMap[$lower]) && $rand() < 0.4) {
            $opts = $leetMap[$lower];
            $result .= $opts[(int) floor($rand() * count($opts))];
        } else {
            $result .= $ch;
        }
    }

    return $result;
}

function transformUnicode(string $word, array $data, Closure $rand): string
{
    $unicodeMap = $data['unicodeMap'];
    $result = '';
    foreach (mb_str_split($word) as $ch) {
        $lower = mb_strtolower($ch);
        if (isset($unicodeMap[$lower]) && $rand() < 0.35) {
            $opts = $unicodeMap[$lower];
            $result .= $opts[(int) floor($rand() * count($opts))];
        } else {
            $result .= $ch;
        }
    }

    return $result;
}

function transformSeparator(string $word, array $data, Closure $rand): string
{
    $chars = mb_str_split($word);
    if (empty($data['separators']) || count($chars) < 2) {
        return $word;
    }
    $sep = $data['separators'][(int) floor($rand() * count($data['separators']))];

    return implode($sep, $chars);
}

function transformSplit(string $word, array $data, Closure $rand): string
{
    $len = mb_strlen($word);
    if ($len < 3) {
        return $word;
    }
    $pos = 1 + (int) floor($rand() * ($len - 1));

    return mb_substr($word, 0, $pos) . ' ' . mb_substr($word, $pos);
}

function transformCase(string $word, array $data, Closure $rand): string
{
    $mode = $rand();
    if ($mode < 0.33) {
        return mb_strtoupper($word);
    }
    $chars = mb_str_split($word);
    if ($mode < 0.66) {
        return implode('', array_map(
            static fn(string $c) => $rand() < 0.5 ? mb_strtoupper($c) : mb_strtolower($c),
            $chars,
        ));
    }

    return implode('', array_map(
        static fn(string $c, int $i) => $i % 2 === 0 ? mb_strtoupper($c) : mb_strtolower($c),
        $chars,
        array_keys($chars),
    ));
}

function transformZalgo(string $word, array $data, Closure $rand): string
{
    if (empty($data['zalgoChars'])) {
        return $word;
    }
    $result = '';
    foreach (mb_str_split($word) as $ch) {
        $result .= $ch;
        $count = 1 + (int) floor($rand() * 3);
        for ($j = 0; $j < $count; $j++) {
            $result .= $data['zalgoChars'][(int) floor($rand() * count($data['zalgoChars']))];
        }
    }

    return $result;
}

function transformZwc(string $word, array $data, Closure $rand): string
{
    $chars = mb_str_split($word);
    if (empty($data['zwcChars']) || count($chars) < 2) {
        return $word;
    }
    $result = '';
    for ($i = 0, $len = count($chars); $i < $len; $i++) {
        $result .= $chars[$i];
        if ($i < $len - 1 && $rand() < 0.5) {
            $result .= $data['zwcChars'][(int) floor($rand() * count($data['zwcChars']))];
        }
    }

    return $result;
}

function transformEmojiMix(string $word, array $data, Closure $rand): string
{
    $len = mb_strlen($word);
    if (empty($data['emojiReplacements']) || $len < 2) {
        return $word;
    }
    $emoji = $data['emojiReplacements'][(int) floor($rand() * count($data['emojiReplacements']))];
    $pos = 1 + (int) floor($rand() * ($len - 1));

    return mb_substr($word, 0, $pos) . $emoji . mb_substr($word, $pos);
}

function transformVowelDrop(string $word, array $data, Closure $rand): string
{
    global $langConfig, $currentLang;
    $vowels = mb_str_split($langConfig[$currentLang]['vowels'] ?? 'aeiou');
    $chars = mb_str_split($word);
    if (count($chars) < 3) {
        return $word;
    }
    $result = '';
    $dropped = false;
    for ($i = 0, $len = count($chars); $i < $len; $i++) {
        if (in_array(mb_strtolower($chars[$i]), $vowels, true) && $i > 0 && $i < $len - 1 && $rand() < 0.5) {
            $dropped = true;

            continue;
        }
        $result .= $chars[$i];
    }

    return $dropped ? $result : $word;
}

function transformReverse(string $word, array $data, Closure $rand): string
{
    $chars = mb_str_split($word);

    return implode('', array_reverse($chars));
}

function transformDoubling(string $word, array $data, Closure $rand): string
{
    $chars = mb_str_split($word);
    if (count($chars) < 2) {
        return $word;
    }
    $idx = (int) floor($rand() * count($chars));
    array_splice($chars, $idx + 1, 0, [$chars[$idx]]);

    return implode('', $chars);
}

$transforms = [
    ['fn' => 'transformSuffix', 'name' => 'suffix', 'family' => 'morphological'],
    ['fn' => 'transformCharRepeat', 'name' => 'charRepeat', 'family' => 'repetition'],
    ['fn' => 'transformLeet', 'name' => 'leet', 'family' => 'substitution'],
    ['fn' => 'transformUnicode', 'name' => 'unicode', 'family' => 'substitution'],
    ['fn' => 'transformSeparator', 'name' => 'separator', 'family' => 'separator'],
    ['fn' => 'transformSplit', 'name' => 'split', 'family' => 'separator'],
    ['fn' => 'transformCase', 'name' => 'case', 'family' => 'casing'],
    ['fn' => 'transformZalgo', 'name' => 'zalgo', 'family' => 'obfuscation'],
    ['fn' => 'transformZwc', 'name' => 'zwc', 'family' => 'obfuscation'],
    ['fn' => 'transformEmojiMix', 'name' => 'emojiMix', 'family' => 'substitution'],
    ['fn' => 'transformVowelDrop', 'name' => 'vowelDrop', 'family' => 'morphological'],
    ['fn' => 'transformReverse', 'name' => 'reverse', 'family' => 'morphological'],
    ['fn' => 'transformDoubling', 'name' => 'doubling', 'family' => 'repetition'],
];

// ============================================================================
// 7. Difficulty Assignment
// ============================================================================
$difficultyWeights = ['easy' => 0.25, 'medium' => 0.35, 'hard' => 0.25, 'extreme' => 0.15];
$difficultyTransforms = [
    'easy' => ['min' => 0, 'max' => 1],
    'medium' => ['min' => 1, 'max' => 2],
    'hard' => ['min' => 2, 'max' => 3],
    'extreme' => ['min' => 3, 'max' => 5],
];

function assignDifficulty(Closure $rand): string
{
    global $difficultyWeights;
    $r = $rand();
    $cum = 0.0;
    foreach ($difficultyWeights as $diff => $w) {
        $cum += $w;
        if ($r <= $cum) {
            return $diff;
        }
    }

    return 'medium';
}

function selectTransforms(string $difficulty, Closure $rand): array
{
    global $transforms, $difficultyTransforms;
    $cfg = $difficultyTransforms[$difficulty];
    $count = $cfg['min'] + (int) floor($rand() * ($cfg['max'] - $cfg['min'] + 1));
    if ($count === 0) {
        return [];
    }

    $shuffled = $transforms;
    usort($shuffled, static fn() => $rand() < 0.5 ? -1 : 1);

    $selected = [];
    $familyCounts = [];

    foreach ($shuffled as $t) {
        if (count($selected) >= $count) {
            break;
        }
        $fc = $familyCounts[$t['family']] ?? 0;
        $familyMax = ($t['family'] === 'substitution' && $difficulty !== 'extreme') ? 1 : 2;
        if ($fc < $familyMax) {
            $selected[] = $t;
            $familyCounts[$t['family']] = $fc + 1;
        }
    }

    return $selected;
}

// ============================================================================
// 8. renderExample
// ============================================================================
function renderPositiveExample(array $data, Closure $rand): array
{
    $root = $data['rootsPositive'][(int) floor($rand() * count($data['rootsPositive']))];
    $difficulty = assignDifficulty($rand);
    $selectedTransforms = selectTransforms($difficulty, $rand);

    $word = $root;
    $appliedTransforms = [];

    foreach ($selectedTransforms as $t) {
        $before = $word;
        $word = ($t['fn'])($word, $data, $rand);
        if ($word !== $before) {
            $appliedTransforms[] = $t['name'];
        }
    }

    $useTemplate = $rand() < 0.7;
    if ($useTemplate && !empty($data['templatesPositive'])) {
        $tpl = $data['templatesPositive'][(int) floor($rand() * count($data['templatesPositive']))];
        $text = str_replace('{word}', $word, $tpl);
    } elseif (!empty($data['contextsPositive'])) {
        $ctx = $data['contextsPositive'][(int) floor($rand() * count($data['contextsPositive']))];
        $text = str_replace('{word}', $word, $ctx);
    } else {
        $text = $word;
    }

    $normalized = \Normalizer::normalize($text, \Normalizer::FORM_C);

    return [
        'text' => $normalized !== false ? $normalized : $text,
        'label' => 1,
        'root' => $root,
        'difficulty' => $difficulty,
        'transforms' => $appliedTransforms,
        'category' => 'positive',
    ];
}

function renderNegativeExample(array $data, Closure $rand): array
{
    $root = $data['rootsNegative'][(int) floor($rand() * count($data['rootsNegative']))];

    $useTemplate = $rand() < 0.7;
    if ($useTemplate && !empty($data['templatesNegative'])) {
        $tpl = $data['templatesNegative'][(int) floor($rand() * count($data['templatesNegative']))];
        $text = str_replace('{word}', $root, $tpl);
    } elseif (!empty($data['contextsNegative'])) {
        $ctx = $data['contextsNegative'][(int) floor($rand() * count($data['contextsNegative']))];
        $text = str_replace('{word}', $root, $ctx);
    } else {
        $text = $root;
    }

    if ($rand() < 0.15) {
        $text = mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    }

    $normalized = \Normalizer::normalize($text, \Normalizer::FORM_C);

    return [
        'text' => $normalized !== false ? $normalized : $text,
        'label' => 0,
        'root' => $root,
        'difficulty' => 'clean',
        'transforms' => [],
        'category' => 'negative',
    ];
}

// ============================================================================
// 9. Shuffle (Fisher-Yates)
// ============================================================================
function fisherYatesShuffle(array &$arr, Closure $rand): void
{
    for ($i = count($arr) - 1; $i > 0; $i--) {
        $j = (int) floor($rand() * ($i + 1));
        [$arr[$i], $arr[$j]] = [$arr[$j], $arr[$i]];
    }
}

// ============================================================================
// 10. Output Writers
// ============================================================================
function writeJsonl(string $filePath, array $examples): void
{
    $fh = fopen($filePath, 'w');
    if ($fh === false) {
        throw new RuntimeException("Failed to open file for writing: {$filePath}");
    }
    foreach ($examples as $ex) {
        fwrite($fh, json_encode($ex, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n");
    }
    fclose($fh);
}

function writeCsv(string $filePath, array $examples): void
{
    $fh = fopen($filePath, 'w');
    if ($fh === false) {
        throw new RuntimeException("Failed to open file for writing: {$filePath}");
    }
    fwrite($fh, "text,label,root,difficulty,transforms,category\n");
    foreach ($examples as $ex) {
        $escapedText = '"' . str_replace('"', '""', $ex['text']) . '"';
        $transformsStr = implode(';', $ex['transforms']);
        fwrite($fh, "{$escapedText},{$ex['label']},{$ex['root']},{$ex['difficulty']},{$transformsStr},{$ex['category']}\n");
    }
    fclose($fh);
}

// ============================================================================
// 11. main()
// ============================================================================
$opts = parseArgs($argv);
$rand = mulberry32($opts['seed']);
$currentLang = $opts['lang'];

$cfg = $langConfig[$opts['lang']];
echo "\n  Synthetic Profanity Dataset Generator (PHP)\n";
echo "  Language: {$cfg['name']} ({$opts['lang']})\n";
echo "  Positive: {$opts['pos']}, Negative: {$opts['neg']}\n";
echo "  Seed: {$opts['seed']}, Format: {$opts['format']}\n";

echo "\n  Loading data files...\n";
$data = loadAllData($opts['data'], $opts['lang']);
echo "  Loaded:\n";
echo "    Positive roots:     " . count($data['rootsPositive']) . "\n";
echo "    Negative roots:     " . count($data['rootsNegative']) . "\n";
echo "    Templates (pos):    " . count($data['templatesPositive']) . "\n";
echo "    Templates (neg):    " . count($data['templatesNegative']) . "\n";
echo "    Suffixes:           " . count($data['suffixes']) . "\n";
echo "    Leet map:           " . count($data['leetMap']) . " chars\n";
echo "    Separators:         " . count($data['separators']) . "\n";

if ($opts['dryRun']) {
    echo "\n  [DRY-RUN] Data loaded successfully. No generation performed.\n";
    exit(0);
}

echo "\n  Generating examples...\n";
$examples = [];
$startTime = microtime(true);

for ($i = 0; $i < $opts['pos']; $i++) {
    $ex = renderPositiveExample($data, $rand);
    if ($opts['difficulty'] !== 'all' && $ex['difficulty'] !== $opts['difficulty']) {
        $retries = 10;
        $filtered = $ex;
        while ($filtered['difficulty'] !== $opts['difficulty'] && $retries > 0) {
            $filtered = renderPositiveExample($data, $rand);
            $retries--;
        }
        $examples[] = $filtered;
    } else {
        $examples[] = $ex;
    }
}

for ($i = 0; $i < $opts['neg']; $i++) {
    $examples[] = renderNegativeExample($data, $rand);
}

$genTime = (int) ((microtime(true) - $startTime) * 1000);
echo "  " . count($examples) . " examples generated ({$genTime}ms)\n";

fisherYatesShuffle($examples, $rand);

if (!is_dir($opts['out'])) {
    mkdir($opts['out'], 0755, true);
}

if ($opts['format'] === 'jsonl' || $opts['format'] === 'both') {
    $jsonlPath = $opts['out'] . "/export-{$opts['lang']}.jsonl";
    echo "  Writing: {$jsonlPath}\n";
    writeJsonl($jsonlPath, $examples);
}

if ($opts['format'] === 'csv' || $opts['format'] === 'both') {
    $csvPath = $opts['out'] . "/export-{$opts['lang']}.csv";
    echo "  Writing: {$csvPath}\n";
    writeCsv($csvPath, $examples);
}

echo "  Done.\n";
