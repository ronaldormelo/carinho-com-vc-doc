<?php

$file = $argv[1] ?? '';
if ($file === '' || !is_file($file)) {
    fwrite(STDERR, "uso: hash-env-app-key.php caminho/.env\n");
    exit(1);
}

$hash = null;
foreach (file($file) as $line) {
    if (!str_starts_with(trim($line), 'APP_KEY=')) {
        continue;
    }
    $value = trim(explode('=', $line, 2)[1] ?? '', " \t\r\n'\"");
    $hash = hash('sha256', $value);
}

if ($hash === null) {
    fwrite(STDERR, "APP_KEY ausente\n");
    exit(1);
}

fwrite(STDOUT, $hash . PHP_EOL);
