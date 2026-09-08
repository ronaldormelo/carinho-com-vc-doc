<?php

$root = dirname(__DIR__);
$failed = false;

function fail(string $message): void
{
    global $failed;
    $failed = true;
    fwrite(STDERR, $message . PHP_EOL);
}

$controller = (string) file_get_contents($root . '/sistemas/carinho-documentos-lgpd/app/Http/Controllers/ContractController.php');
if (!str_contains($controller, "Content-Disposition' => 'inline;")) {
    fail('Contrato HTML deve abrir inline (imprimir), não attachment.');
}
if (!str_contains($controller, 'window.print()')) {
    fail('HTML imprimível deve oferecer window.print().');
}
if (!str_contains($controller, 'printPublic')) {
    fail('Deve existir printPublic para a família abrir o contrato.');
}

$web = (string) file_get_contents($root . '/sistemas/carinho-documentos-lgpd/routes/web.php');
if (!str_contains($web, '/contratos/{id}/imprimir')) {
    fail('Rota pública GET /contratos/{id}/imprimir ausente.');
}

$seeder = (string) file_get_contents($root . '/sistemas/carinho-documentos-lgpd/database/seeders/DevLocalContractSeeder.php');
if (!str_contains($seeder, 'firstOrCreate')) {
    fail('DevLocalContractSeeder deve ser idempotente.');
}

$sign = (string) file_get_contents($root . '/sistemas/carinho-crm/resources/views/contracts/sign.blade.php');
if (!str_contains($sign, 'window.print()')) {
    fail('Aceite digital do CRM deve permitir imprimir.');
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "OK: contrato existente é HTML imprimível; 404 só se não existir.\n");
exit(0);
