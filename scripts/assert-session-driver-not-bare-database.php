<?php

/**
 * Laravel 11 defaults SESSION_DRIVER to database. Without a sessions table
 * that 500s (1146). Compose must set a non-database driver (redis/file).
 */
$root = dirname(__DIR__);
$failed = false;

function fail(string $message) : void
{
    global $failed;
    $failed = true;
    fwrite(STDERR, $message . PHP_EOL);
}

$composefiles = glob($root . '/sistemas/*/docker-compose.yml') ?: [];
if (count($composefiles) < 9) {
    fail('Esperados 9 docker-compose.yml');
}

foreach ($composefiles as $file) {
    $src = (string) file_get_contents($file);
    if (!preg_match('/SESSION_DRIVER:\s*.+/', $src)) {
        fail($file . ': SESSION_DRIVER deve estar no environment do Compose (Laravel 11 default = database)');
    }
    if (preg_match('/SESSION_DRIVER:\s*database\b/', $src) === 1) {
        fail($file . ': SESSION_DRIVER=database exige tabela sessions; use redis ou file');
    }
}

$migrations = glob($root . '/sistemas/*/database/migrations/*create_sessions_table.php') ?: [];
if (count($migrations) < 9) {
    fail('Esperadas 9 migrations create_sessions_table; encontradas ' . count($migrations));
}

$siteCompose = (string) file_get_contents($root . '/sistemas/carinho-site/docker-compose.yml');
if (!preg_match('/SESSION_DRIVER:\s*\$\{SESSION_DRIVER:-redis\}/', $siteCompose)
    && !preg_match('/SESSION_DRIVER:\s*redis\b/', $siteCompose)) {
    fail('carinho-site deve injetar SESSION_DRIVER redis no container');
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "OK: sessão não cai no default database sem tabela.\n");
exit(0);
