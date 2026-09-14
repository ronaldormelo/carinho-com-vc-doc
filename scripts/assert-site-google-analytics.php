<?php

/**
 * Google Analytics 4 (gtag.js) no site institucional.
 *
 * O Measurement ID G-WLV8231QBM deve estar no layout compartilhado,
 * na config e no .env.example, para que todas as páginas que estendem
 * layouts.app carreguem o tag.
 */
$root = dirname(__DIR__);
$failed = false;
$measurementId = 'G-WLV8231QBM';

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

$config = $root . '/sistemas/carinho-site/config/integrations.php';
$example = $root . '/sistemas/carinho-site/.env.example';
$layoutRel = 'sistemas/carinho-site/resources/views/layouts/app.blade.php';
$layout = $root . '/' . $layoutRel;

assertContains($config, "env('GA4_MEASUREMENT_ID') ?: '{$measurementId}'");
assertContains($example, 'GA4_MEASUREMENT_ID=' . $measurementId);
assertContains($example, 'ANALYTICS_ENABLED=true');

if (!is_file($layout)) {
    fail($layoutRel . ': layout ausente');
} else {
    $contents = file_get_contents($layout);
    if ($contents === false) {
        fail($layoutRel . ': não foi possível ler o arquivo');
    } else {
        $needles = [
            "https://www.googletagmanager.com/gtag/js?id={{ config('integrations.analytics.ga4_id') }}",
            "gtag('js', new Date());",
            "gtag('config', '{{ config('integrations.analytics.ga4_id') }}');",
            "config('integrations.analytics.enabled') && config('integrations.analytics.ga4_id')",
        ];
        foreach ($needles as $needle) {
            if (!str_contains($contents, $needle)) {
                fail($layoutRel . ': ausente ' . $needle);
            }
        }
    }
}

$configRoot = dirname($root) . '/carinho-com-vc-doc-config/carinho-site';
foreach (['.env.production', '.env.testing'] as $envFile) {
    $path = $configRoot . '/' . $envFile;
    if (!is_file($path)) {
        continue;
    }
    $envContents = file_get_contents($path);
    if ($envContents === false) {
        fail($path . ': não foi possível ler o arquivo');
        continue;
    }
    if (!preg_match('/^GA4_MEASUREMENT_ID=/m', $envContents)) {
        fail($path . ': ausente GA4_MEASUREMENT_ID');
        continue;
    }
    if (preg_match('/^GA4_MEASUREMENT_ID=(.*)$/m', $envContents, $matches)) {
        $value = trim($matches[1], " \t\"'");
        if ($value !== '' && $value !== $measurementId) {
            fail($path . ': GA4_MEASUREMENT_ID deveria ser ' . $measurementId . ' ou vazio (fallback da config)');
        }
    }
}

$viewsRoot = $root . '/sistemas/carinho-site/resources/views';
foreach (['pages', 'legal'] as $dir) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($viewsRoot . '/' . $dir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile() || !str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $rel = str_replace($root . '/', '', $file->getPathname());
        $contents = file_get_contents($file->getPathname());
        if ($contents === false) {
            fail($rel . ': não foi possível ler o arquivo');
            continue;
        }

        if (!str_contains($contents, "@extends('layouts.app')")) {
            fail($rel . ': deve estender layouts.app para receber o gtag do GA4');
        }
    }
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "OK: GA4 {$measurementId} via gtag.js no layout do site.\n");
exit(0);
