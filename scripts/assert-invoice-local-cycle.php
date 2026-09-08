<?php

$root = dirname(__DIR__);
$failed = false;

function fail(string $message): void
{
    global $failed;
    $failed = true;
    fwrite(STDERR, $message . PHP_EOL);
}

$resource = (string) file_get_contents($root . '/sistemas/carinho-financeiro/app/Http/Resources/InvoiceItemResource.php');
if (!str_contains($resource, "Schema::hasColumn('invoice_items', 'service_type_id')")) {
    fail('InvoiceItemResource deve evitar lazy-load de serviceType se a coluna não existir.');
}

$seeder = (string) file_get_contents($root . '/sistemas/carinho-financeiro/database/seeders/DevLocalInvoiceSeeder.php');
if (!str_contains($seeder, 'DEV-LOCAL-INVOICE')) {
    fail('DevLocalInvoiceSeeder deve criar fatura com referência DEV-LOCAL-INVOICE.');
}
if (!str_contains($seeder, 'firstOrCreate')) {
    fail('DevLocalInvoiceSeeder deve ser idempotente (firstOrCreate).');
}

$service = (string) file_get_contents($root . '/sistemas/carinho-financeiro/app/Services/InvoiceService.php');
if (!str_contains($service, "Schema::hasColumn('invoice_items'")) {
    fail('InvoiceService::addItem deve gravar só colunas existentes em invoice_items.');
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "OK: ciclo mínimo de fatura não assume colunas fantasma.\n");
exit(0);
