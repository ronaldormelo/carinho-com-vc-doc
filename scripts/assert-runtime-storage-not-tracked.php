<?php

/**
 * Compiled views, file sessions and cache payloads are rewritten on the VPS.
 * If they stay in Git, `git pull` aborts: local changes would be overwritten.
 */
$root = dirname(__DIR__);
$failed = false;

function fail(string $message) : void
{
    global $failed;
    $failed = true;
    fwrite(STDERR, $message . PHP_EOL);
}

$tracked = [];
$gitExit = 0;
exec(
    'git -C ' . escapeshellarg($root) . ' ls-files -- sistemas',
    $tracked,
    $gitExit
);
if ($gitExit !== 0) {
    fail('git ls-files falhou com código ' . $gitExit);
}

$bad = array_values(array_filter(
    $tracked,
    static function (string $path): bool {
        if (preg_match('#storage/framework/views/.+\.php$#', $path) === 1) {
            return true;
        }
        if (preg_match('#storage/framework/sessions/#', $path) === 1 && !str_ends_with($path, '.gitignore')) {
            return true;
        }
        if (preg_match('#storage/framework/cache/data/#', $path) === 1 && !str_ends_with($path, '.gitignore')) {
            return true;
        }

        return false;
    }
));

if ($bad !== []) {
    $shown = array_slice($bad, 0, 20);
    $more = count($bad) > 20 ? "\n - ... +" . (count($bad) - 20) . ' arquivos' : '';
    fail(
        "Artefatos de runtime do Laravel não podem estar no Git (deploy na VPS quebra):\n - "
        . implode("\n - ", $shown)
        . $more
    );
}

$keep = array_values(array_filter(
    $tracked,
    static fn (string $path): bool => (bool) preg_match('#storage/framework/cache/data/\\.gitignore$#', $path)
));
if (count($keep) < 9) {
    fail('Esperados 9 storage/framework/cache/data/.gitignore rastreados; encontrados ' . count($keep));
}

$gitignore = (string) file_get_contents($root . '/.gitignore');
foreach (['**/storage/framework/views/*', '**/storage/framework/sessions/*'] as $needle) {
    if (!str_contains($gitignore, $needle)) {
        fail('.gitignore da raiz deve ignorar ' . $needle);
    }
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "OK: views/sessões/cache de runtime fora do Git.\n");
exit(0);
