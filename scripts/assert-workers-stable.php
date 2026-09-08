<?php

$root = dirname(__DIR__);
$failed = false;

function fail(string $message): void
{
    global $failed;
    $failed = true;
    fwrite(STDERR, $message . PHP_EOL);
}

foreach (glob($root . '/sistemas/*/docker-compose.yml') ?: [] as $file) {
    $src = (string) file_get_contents($file);
    $name = basename(dirname($file));
    if (!str_contains($src, 'docker-entrypoint.sh')) {
        fail($name . ': compose deve forçar o entrypoint do bind-mount (imagem antiga ainda tem key:generate).');
    }
}

$marketing = (string) file_get_contents($root . '/sistemas/carinho-marketing/docker-compose.yml');
$integracoes = (string) file_get_contents($root . '/sistemas/carinho-integracoes/docker-compose.yml');

foreach (['marketing' => $marketing, 'integracoes' => $integracoes] as $name => $src) {
    if (str_contains($src, '--max-jobs=')) {
        fail($name . ': queue:work não deve usar --max-jobs (worker deve permanecer Up).');
    }
    if (!str_contains($src, 'queue:work')) {
        fail($name . ': deve usar queue:work, não farm Horizon.');
    }
    if (!str_contains($src, 'mem_limit:')) {
        fail($name . ': worker/scheduler precisa de mem_limit contra OOM 137.');
    }
    if (!str_contains($src, 'docker-entrypoint.sh')) {
        fail($name . ': worker deve usar o entrypoint do bind-mount, não o da imagem antiga.');
    }
}

if (preg_match("/command:\\s*php artisan horizon\\b/", $integracoes)) {
    fail('integracoes: não subir Horizon farm neste ciclo.');
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "OK: workers queue:work estáveis, sem --max-jobs.\n");
exit(0);
