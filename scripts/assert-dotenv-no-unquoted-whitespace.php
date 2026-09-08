<?php

/**
 * vlucas/phpdotenv (Laravel) rejects unquoted values that contain whitespace:
 *   Failed to parse dotenv file. Encountered unexpected whitespace at [...]
 */
$root = dirname(__DIR__);
$failed = false;

function fail(string $message) : void
{
    global $failed;
    $failed = true;
    fwrite(STDERR, $message . PHP_EOL);
}

/**
 * @return list<string>
 */
function dotenvFiles(string $root) : array
{
    $files = glob($root . '/sistemas/*/.env.example') ?: [];
    $configRoot = dirname($root) . '/carinho-com-vc-doc-config';
    if (is_dir($configRoot)) {
        foreach (glob($configRoot . '/*/.env.*') ?: [] as $file) {
            $files[] = $file;
        }
    }

    return $files;
}

function lineHasUnquotedWhitespace(string $line) : bool
{
    $trim = trim($line);
    if ($trim === '' || str_starts_with($trim, '#')) {
        return false;
    }
    if (str_starts_with($trim, 'export ')) {
        $trim = trim(substr($trim, 7));
    }
    $eq = strpos($trim, '=');
    if ($eq === false) {
        return false;
    }
    $value = substr($trim, $eq + 1);
    if ($value === '') {
        return false;
    }
    $first = $value[0];
    if ($first === '"' || $first === "'") {
        return false;
    }

    return (bool) preg_match('/\s/', $value);
}

function dotenvKey(string $line) : string
{
    $trim = trim($line);
    if (str_starts_with($trim, 'export ')) {
        $trim = trim(substr($trim, 7));
    }
    $eq = strpos($trim, '=');

    return $eq === false ? '?' : substr($trim, 0, $eq);
}

$files = dotenvFiles($root);
if ($files === []) {
    fail('Nenhum .env.example encontrado em sistemas/*');
}

foreach ($files as $file) {
    $lines = file($file, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        fail($file . ': não foi possível ler o arquivo');
        continue;
    }
    foreach ($lines as $number => $line) {
        if (!lineHasUnquotedWhitespace($line)) {
            continue;
        }
        $rel = str_replace('\\', '/', $file);
        fail(sprintf(
            '%s:%d chave %s tem espaço sem aspas (phpdotenv rejeita)',
            $rel,
            $number + 1,
            dotenvKey($line)
        ));
    }
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, 'OK: valores com espaço nos .env estão entre aspas.' . PHP_EOL);
exit(0);
