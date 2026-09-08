<?php

$root = dirname(__DIR__);
$failed = false;

function fail(string $message): void
{
    global $failed;
    $failed = true;
    fwrite(STDERR, $message . PHP_EOL);
}

$seeder = (string) file_get_contents($root . '/sistemas/carinho-site/database/seeders/TestimonialSeeder.php');
if (!str_contains($seeder, 'Carinho com Você')) {
    fail('TestimonialSeeder deve persistir a marca com acento (Você).');
}
if (str_contains($seeder, 'Carinho com Voce')) {
    fail('TestimonialSeeder ainda contém "Voce" sem acento.');
}
if (!str_contains($seeder, 'updateOrCreate')) {
    fail('TestimonialSeeder deve atualizar copy já seedada (updateOrCreate), não só firstOrCreate.');
}

$dashboard = (string) file_get_contents($root . '/sistemas/carinho-crm/resources/views/dashboard/index.blade.php');
if (str_contains($dashboard, '/api/v1/reports/dashboard')) {
    fail('Dashboard autenticado não deve depender de Sanctum /api/v1/reports/dashboard.');
}
if (!str_contains($dashboard, 'dashboard.data')) {
    fail('Dashboard deve buscar /dashboard/data na sessão web.');
}
if (!str_contains($dashboard, 'AbortController')) {
    fail('Dashboard deve abortar fetch lento para não ficar em Carregando.');
}

$web = (string) file_get_contents($root . '/sistemas/carinho-crm/routes/web.php');
if (!str_contains($web, '/dashboard/data')) {
    fail('Rota web /dashboard/data ausente.');
}

$layout = (string) file_get_contents($root . '/sistemas/carinho-crm/resources/views/layouts/app.blade.php');
if (preg_match('/\bAuth::user\(\)/', $layout)) {
    fail('layouts/app.blade.php não deve usar o alias Auth (não está no config/app.php padrão deste módulo).');
}

$compose = (string) file_get_contents($root . '/sistemas/carinho-crm/docker-compose.yml');
if (preg_match('/DB_PASSWORD:\s*\$\{DB_PASSWORD:-carinho\}/', $compose) || preg_match('/DB_PASSWORD:\s*carinho\b/', $compose)) {
    fail('CRM compose não deve forçar senha carinho sobre .env local com root vazio.');
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "OK: depoimentos Você + dashboard de sessão com timeout.\n");
exit(0);
