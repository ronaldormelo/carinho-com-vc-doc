<?php

/**
 * setting_categories has no timestamps (2026_01_22 / schema.sql).
 * Seeding created_at there aborts migrate --force with MySQL 1054.
 */
$root = dirname(__DIR__);
$file = $root . '/sistemas/carinho-financeiro/database/migrations/2026_01_23_000001_add_financial_controls.php';
$src = (string) file_get_contents($file);
$failed = false;

function fail(string $message) : void
{
    global $failed;
    $failed = true;
    fwrite(STDERR, $message . PHP_EOL);
}

if (!preg_match('/function seedApprovalSettings\s*\(\s*\)\s*:\s*void\s*\{([\s\S]*?)\n    \}/', $src, $match)) {
    fail('seedApprovalSettings() ausente em 2026_01_23_000001');
    $body = '';
} else {
    $body = $match[1];
}

if ($body !== '' && !str_contains($body, "hasTable('setting_categories')")) {
    fail('seed de approval deve pular se setting_categories não existir');
}

if (!str_contains($src, 'getColumnListing')) {
    fail('seed de approval deve gravar só colunas existentes (setting_categories sem created_at)');
}

if (preg_match("/setting_categories'\)->insertGetId\(\s*\[[^\]]*created_at/", $src) === 1) {
    fail('insert em setting_categories não pode mandar created_at sem filtrar colunas');
}

$createSettings = (string) file_get_contents(
    $root . '/sistemas/carinho-financeiro/database/migrations/2026_01_22_000001_create_settings_table.php'
);
if (preg_match(
    '/Schema::create\(\'setting_categories\',\s*function\s*\(Blueprint \$table\)\s*\{([^}]+)\}/',
    $createSettings,
    $createMatch
) !== 1) {
    fail('create setting_categories não encontrado em 2026_01_22');
} elseif (str_contains($createMatch[1], 'timestamps')) {
    fail('setting_categories não tem timestamps; o seed não pode assumir created_at');
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "OK: seed de approval não usa timestamps em setting_categories.\n");
exit(0);
