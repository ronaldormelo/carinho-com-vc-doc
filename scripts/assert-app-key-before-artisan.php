<?php

$root = dirname(__DIR__);
$failed = false;

function fail(string $message) : void
{
    global $failed;
    $failed = true;
    fwrite(STDERR, $message . PHP_EOL);
}

$entrypoints = glob($root . '/sistemas/*/docker-entrypoint.sh') ?: [];
if (count($entrypoints) < 9) {
    fail('Esperados 9 docker-entrypoint.sh');
}

foreach ($entrypoints as $file) {
    $src = (string) file_get_contents($file);
    $ensure = strpos($src, 'ensure-app-key.sh');
    $discover = strpos($src, 'php artisan package:discover');
    $artisanKey = strpos($src, 'key:generate');
    if ($ensure === false || $discover === false || $ensure > $discover) {
        fail($file . ': ensure-app-key.sh deve rodar antes de package:discover');
    }
    if ($artisanKey !== false) {
        fail($file . ': não usar artisan key:generate (bootstrap exige APP_KEY)');
    }
}

$script = $root . '/scripts/ensure-app-key.sh';
if (!is_file($script)) {
    fail('scripts/ensure-app-key.sh ausente');
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "OK: APP_KEY é gravada no .env antes do Artisan.\n");
exit(0);
