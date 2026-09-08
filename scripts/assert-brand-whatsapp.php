<?php

/**
 * WhatsApp da marca: (89) 99977-1471 / 5589999771471
 */
$root = dirname(__DIR__);
$failed = false;
$e164 = '5589999771471';
$display = '(89) 99977-1471';

function fail(string $message) : void
{
    global $failed;
    $failed = true;
    fwrite(STDERR, $message . PHP_EOL);
}

function assertContains(string $file, string $needle) : void
{
    $contents = file_get_contents($file);
    if ($contents === false) {
        fail($file . ': não foi possível ler o arquivo');
        return;
    }
    if (!str_contains($contents, $needle)) {
        fail($file . ': ausente ' . $needle);
    }
}

$branding = $root . '/sistemas/carinho-site/config/branding.php';
$example = $root . '/sistemas/carinho-site/.env.example';
assertContains($branding, "env('BRAND_WHATSAPP', '{$e164}')");
assertContains($branding, "env('BRAND_WHATSAPP_DISPLAY', '{$display}')");
assertContains($example, 'BRAND_WHATSAPP=' . $e164);
assertContains($example, 'BRAND_WHATSAPP_DISPLAY="' . $display . '"');

$configRoot = dirname($root) . '/carinho-com-vc-doc-config/carinho-site';
foreach (['.env.production', '.env.testing'] as $envFile) {
    $path = $configRoot . '/' . $envFile;
    if (!is_file($path)) {
        continue;
    }
    assertContains($path, 'BRAND_WHATSAPP=' . $e164);
    assertContains($path, 'BRAND_WHATSAPP_DISPLAY="' . $display . '"');
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "OK: WhatsApp da marca é {$display} ({$e164}).\n");
exit(0);
