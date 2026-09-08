<?php

$configRoot = dirname(__DIR__) . '/../carinho-com-vc-doc-config';
$configRoot = realpath($configRoot) ?: $configRoot;
if (!is_dir($configRoot)) {
    fwrite(STDERR, "repo de config não encontrado\n");
    exit(1);
}

$files = glob($configRoot . '/*/.env.*') ?: [];
$filled = 0;
foreach ($files as $file) {
    $contents = (string) file_get_contents($file);
    if (!preg_match('/^APP_KEY=\s*$/m', $contents) && !preg_match('/^APP_KEY=""\s*$/m', $contents)) {
        continue;
    }
    $key = 'base64:' . base64_encode(random_bytes(32));
    $updated = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $key, $contents, 1);
    if (!is_string($updated) || $updated === $contents) {
        fwrite(STDERR, "falha ao gravar APP_KEY em " . basename(dirname($file)) . '/' . basename($file) . PHP_EOL);
        exit(1);
    }
    file_put_contents($file, $updated);
    $filled++;
}

fwrite(STDOUT, "APP_KEY preenchida em {$filled} arquivo(s) do repo de config.\n");
