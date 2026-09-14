<?php

/**
 * Formulários públicos do Site exigem linhas em lead_forms.
 * O deploy na VPS deve semear LeadFormSeeder após migrate — não o
 * comando local `docker exec carinho-site-app`.
 */
$root = dirname(__DIR__);
$file = $root . '/.github/workflows/deploy.yml';
$src = (string) file_get_contents($file);
$failed = false;

function fail(string $message) : void
{
    global $failed;
    $failed = true;
    fwrite(STDERR, $message . PHP_EOL);
}

if (!str_contains($src, 'php artisan migrate --force')) {
    fail('deploy.yml deve rodar migrate --force');
}

if (!str_contains($src, 'db:seed --class=LeadFormSeeder --force')) {
    fail('deploy.yml deve semear LeadFormSeeder no carinho-site após migrate');
}

if (!str_contains($src, 'carinho-site') || !str_contains($src, 'LeadFormSeeder')) {
    fail('LeadFormSeeder no deploy deve estar associado ao carinho-site');
}

if (str_contains($src, 'docker exec carinho-site-app php artisan db:seed')) {
    fail('deploy da VPS não usa o nome local docker exec carinho-site-app');
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "OK: deploy semeia LeadFormSeeder no Site após migrate.\n");
exit(0);
