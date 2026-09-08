<?php

/**
 * Production (`composer install --no-dev`) does not ship nunomaduro/collision.
 * Laravel still boots providers from bootstrap/cache/packages.php if that file
 * is present — including when it is checked into Git from a local --dev install.
 */
$root = dirname(__DIR__);
$failed = false;

function fail(string $message) : void
{
    global $failed;
    $failed = true;
    fwrite(STDERR, $message . PHP_EOL);
}

$composerFiles = glob($root . '/sistemas/*/composer.json') ?: [];
if (count($composerFiles) < 9) {
    fail('Esperados 9 composer.json em sistemas/*; encontrados ' . count($composerFiles));
}

foreach ($composerFiles as $file) {
    $json = json_decode((string) file_get_contents($file), true);
    if (!is_array($json)) {
        fail($file . ': JSON inválido');
        continue;
    }
    $dontDiscover = $json['extra']['laravel']['dont-discover'] ?? null;
    if (!is_array($dontDiscover) || !in_array('nunomaduro/collision', $dontDiscover, true)) {
        fail($file . ': extra.laravel.dont-discover deve incluir nunomaduro/collision');
    }
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
$tracked = array_values(array_filter(
    $tracked,
    static fn (string $path): bool => (bool) preg_match('#bootstrap/cache/(packages|services)\\.php$#', $path)
));
if ($tracked !== []) {
    fail("Arquivos de cache Laravel não podem estar no Git (Collision em --no-dev):\n - " . implode("\n - ", $tracked));
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "OK: dont-discover de Collision e cache de packages fora do Git.\n");
exit(0);
