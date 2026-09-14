<?php

/**
 * WhatsApp da marca: (61) 99512-5883 / 5561995125883
 *
 * O número visível no site deve ser um link direto (componente x-whatsapp-number
 * apontando para a rota whatsapp.cta → wa.me). JSON-LD telephone não é UI.
 */
$root = dirname(__DIR__);
$failed = false;
$e164 = '5561995125883';
$display = '(61) 99512-5883';
$legacyE164 = '5589999771471';
$legacyDisplay = '(89) 99977-1471';
$legacyDigits = '89999771471';

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

function assertNotContains(string $file, string $needle) : void
{
    $contents = file_get_contents($file);
    if ($contents === false) {
        fail($file . ': não foi possível ler o arquivo');
        return;
    }
    if (str_contains($contents, $needle)) {
        fail($file . ': ainda contém número antigo ' . $needle);
    }
}

$branding = $root . '/sistemas/carinho-site/config/branding.php';
$example = $root . '/sistemas/carinho-site/.env.example';
assertContains($branding, "env('BRAND_WHATSAPP', '{$e164}')");
assertContains($branding, "env('BRAND_WHATSAPP_DISPLAY', '{$display}')");
assertContains($example, 'BRAND_WHATSAPP=' . $e164);
assertContains($example, 'BRAND_WHATSAPP_DISPLAY="' . $display . '"');

$marketingBranding = $root . '/sistemas/carinho-marketing/config/branding.php';
$marketingExample = $root . '/sistemas/carinho-marketing/.env.example';
assertContains($marketingBranding, "env('BRAND_WHATSAPP', '{$e164}')");
assertContains($marketingBranding, "env('BRAND_WHATSAPP_DISPLAY', '{$display}')");
assertContains($marketingExample, 'BRAND_WHATSAPP=' . $e164);
assertContains($marketingExample, 'BRAND_WHATSAPP_DISPLAY="' . $display . '"');

$financeiroBranding = $root . '/sistemas/carinho-financeiro/config/branding.php';
$financeiroExample = $root . '/sistemas/carinho-financeiro/.env.example';
assertContains($financeiroBranding, "env('COMPANY_PHONE', '{$e164}')");
assertContains($financeiroExample, 'COMPANY_PHONE=' . $e164);

$configRoot = dirname($root) . '/carinho-com-vc-doc-config/carinho-site';
foreach (['.env.production', '.env.testing'] as $envFile) {
    $path = $configRoot . '/' . $envFile;
    if (!is_file($path)) {
        continue;
    }
    assertContains($path, 'BRAND_WHATSAPP=' . $e164);
    assertContains($path, 'BRAND_WHATSAPP_DISPLAY="' . $display . '"');
}

$componentRel = 'sistemas/carinho-site/resources/views/components/whatsapp-number.blade.php';
$layoutRel = 'sistemas/carinho-site/resources/views/layouts/app.blade.php';
$componentPath = $root . '/' . $componentRel;

if (!is_file($componentPath)) {
    fail($componentRel . ': componente ausente');
} else {
    $component = file_get_contents($componentPath);
    if ($component === false) {
        fail($componentRel . ': não foi possível ler o arquivo');
    } else {
        if (!str_contains($component, "route('whatsapp.cta'")) {
            fail($componentRel . ': deve apontar para route(\'whatsapp.cta\')');
        }
        if (!str_contains($component, "config('branding.contact.whatsapp_display')")) {
            fail($componentRel . ': deve exibir branding.contact.whatsapp_display');
        }
        if (!preg_match("/href=\"\{\{\s*route\('whatsapp\.cta',\s*\['msg'\s*=>\s*\\\$msg\]\)\s*\}\}\"/", $component)) {
            fail($componentRel . ': o número precisa ser um <a href="{{ route(\'whatsapp.cta\', [\'msg\' => $msg]) }}">');
        }
        if (!str_contains($component, "@props(['msg' => 'contact'])")) {
            fail($componentRel . ': deve aceitar msg contextual (padrão contact)');
        }
    }
}

$pagesThatMustLinkNumber = [
    'sistemas/carinho-site/resources/views/partials/footer.blade.php',
    'sistemas/carinho-site/resources/views/pages/contact.blade.php',
    'sistemas/carinho-site/resources/views/pages/investors.blade.php',
    'sistemas/carinho-site/resources/views/legal/caregiver-terms.blade.php',
    'sistemas/carinho-site/resources/views/legal/emergency.blade.php',
    'sistemas/carinho-site/resources/views/legal/cancellation.blade.php',
    'sistemas/carinho-site/resources/views/legal/payment.blade.php',
    'sistemas/carinho-site/resources/views/legal/terms.blade.php',
];

foreach ($pagesThatMustLinkNumber as $rel) {
    assertContains($root . '/' . $rel, '<x-whatsapp-number');
}

$viewsRoot = $root . '/sistemas/carinho-site/resources/views';
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($viewsRoot, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (!$file->isFile() || !str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }

    $rel = str_replace($root . '/', '', $file->getPathname());
    if ($rel === $componentRel) {
        continue;
    }

    $contents = file_get_contents($file->getPathname());
    if ($contents === false) {
        fail($rel . ': não foi possível ler o arquivo');
        continue;
    }

    $displayUsages = substr_count($contents, "config('branding.contact.whatsapp_display')");
    if ($displayUsages === 0) {
        continue;
    }

    if ($rel === $layoutRel) {
        if ($displayUsages !== 1) {
            fail($rel . ': o número visível deve usar <x-whatsapp-number />; JSON-LD telephone é a única exceção');
        }
        if (!str_contains($contents, '"telephone": "{{ config(\'branding.contact.whatsapp_display\') }}"')) {
            fail($rel . ': JSON-LD telephone deve manter o número da marca');
        }
        continue;
    }

    fail($rel . ': o número visível do WhatsApp deve usar <x-whatsapp-number />');
}

$scanRoots = [
    $root . '/sistemas',
    $root . '/scripts',
];
$legacyNeedles = [$legacyE164, $legacyDisplay, $legacyDigits];
$skipDirs = ['vendor', 'node_modules', 'storage', '.git'];

foreach ($scanRoots as $scanRoot) {
    if (!is_dir($scanRoot)) {
        continue;
    }

    $scan = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($scanRoot, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($scan as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $path = $file->getPathname();
        $rel = str_replace($root . '/', '', $path);
        if ($rel === 'scripts/assert-brand-whatsapp.php') {
            continue;
        }
        if ($file->getFilename() === '.env') {
            continue;
        }

        foreach ($skipDirs as $skip) {
            if (str_contains($path, DIRECTORY_SEPARATOR . $skip . DIRECTORY_SEPARATOR)) {
                continue 2;
            }
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['php', 'md', 'example', 'env', 'json', 'yml', 'yaml', 'blade.php', 'txt', 'css', 'js'], true)
            && !str_ends_with($file->getFilename(), '.blade.php')
            && !str_ends_with($file->getFilename(), '.env.example')) {
            continue;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            continue;
        }

        foreach ($legacyNeedles as $needle) {
            if (str_contains($contents, $needle)) {
                fail(str_replace($root . '/', '', $path) . ': ainda contém número antigo ' . $needle);
            }
        }
    }
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "OK: WhatsApp da marca é {$display} ({$e164}) e o número visível é um link.\n");
exit(0);
