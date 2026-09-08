<?php

/**
 * cash_transactions/payables cannot FK bank_accounts until that table exists
 * (2026_09_07_000002). MariaDB errno 150 otherwise.
 */
$root = dirname(__DIR__);
$january = $root . '/sistemas/carinho-financeiro/database/migrations/2026_01_23_000001_add_financial_controls.php';
$september = $root . '/sistemas/carinho-financeiro/database/migrations/2026_09_07_000002_create_billing_core_tables.php';
$fk = $root . '/sistemas/carinho-financeiro/database/migrations/2026_09_07_000003_add_bank_account_foreign_keys.php';
$failed = false;

function fail(string $message) : void
{
    global $failed;
    $failed = true;
    fwrite(STDERR, $message . PHP_EOL);
}

$januarySrc = (string) file_get_contents($january);
if (preg_match("/->on\\(\\s*['\\\"]bank_accounts['\\\"]\\s*\\)/", $januarySrc)) {
    fail('2026_01_23 não pode criar FK para bank_accounts (tabela só existe em setembro).');
}

$septemberSrc = (string) file_get_contents($september);
if (!str_contains($septemberSrc, "Schema::create('bank_accounts'")) {
    fail('2026_09_07_000002 deve criar bank_accounts.');
}

$fkSrc = (string) file_get_contents($fk);
if ($fkSrc === '' || !str_contains($fkSrc, 'cash_transactions') || !str_contains($fkSrc, 'bank_accounts')) {
    fail('2026_09_07_000003 deve criar a FK cash_transactions.bank_account_id → bank_accounts.');
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "OK: FK de bank_accounts só depois da tabela existir.\n");
exit(0);
