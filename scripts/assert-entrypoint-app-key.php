<?php

$root = dirname(__DIR__);
$failed = false;

function fail(string $message): void
{
    global $failed;
    $failed = true;
    fwrite(STDERR, $message . PHP_EOL);
}

foreach (glob($root . '/sistemas/*/docker-entrypoint.sh') ?: [] as $file) {
    $src = (string) file_get_contents($file);
    if (str_contains($src, 'key:generate')) {
        fail($file . ': não usar artisan key:generate (bootstrap exige APP_KEY).');
    }
    if (!str_contains($src, 'ensure-app-key.sh')) {
        fail($file . ': deve chamar ensure-app-key.sh.');
    }
    if (!str_contains($src, 'vendor/autoload.php presente') && !str_contains($src, 'pulando composer install')) {
        fail($file . ': deve pular composer install se vendor existir.');
    }
    if (!str_contains($src, 'pulando package:discover')) {
        fail($file . ': worker/scheduler deve pular package:discover.');
    }
}

$ensure = (string) file_get_contents($root . '/scripts/ensure-app-key.sh');
if (!str_contains($ensure, '-ge 20') || !str_contains($ensure, 'read_key')) {
  fail('ensure-app-key.sh deve recusar regenerar chave já preenchida.');
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "OK: entrypoint não regenera APP_KEY nem reinstala vendor às cegas.\n");
exit(0);
