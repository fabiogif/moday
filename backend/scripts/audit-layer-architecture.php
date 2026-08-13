#!/usr/bin/env php
<?php

/**
 * Audita aderência ao padrão Controller → Service → Repository → Interface.
 *
 * Uso: php scripts/audit-layer-architecture.php
 */

$basePath = dirname(__DIR__);

function scanPhpFiles(string $directory): array
{
    if (!is_dir($directory)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

function fileUsesPattern(string $content, string $pattern): bool
{
    return (bool) preg_match($pattern, $content);
}

function relativePath(string $base, string $path): string
{
    return str_replace('\\', '/', substr($path, strlen($base) + 1));
}

echo "=== Auditoria de Camadas SOLID ===\n";
echo "Base: {$basePath}\n\n";

$violations = [];

// 1. Controllers API com Models ou DB direto
$controllerFiles = scanPhpFiles($basePath . '/app/Http/Controllers/Api');
foreach ($controllerFiles as $file) {
    $content = file_get_contents($file);
    $rel = relativePath($basePath, $file);

    if (fileUsesPattern($content, '/use\s+App\\\\Models\\\\/')) {
        $violations['controllers_with_models'][] = $rel;
    }
    if (fileUsesPattern($content, '/use\s+Illuminate\\\\Support\\\\Facades\\\\DB;/')
        || fileUsesPattern($content, '/\bDB::/')) {
        $violations['controllers_with_db'][] = $rel;
    }
    if (fileUsesPattern($content, '/RepositoryInterface/')
        && !fileUsesPattern($content, '/Service/')) {
        $violations['controllers_with_repository_no_service'][] = $rel;
    }
}

// 2. Services com Models sem RepositoryInterface
$serviceFiles = scanPhpFiles($basePath . '/app/Services');
foreach ($serviceFiles as $file) {
    $content = file_get_contents($file);
    $rel = relativePath($basePath, $file);

    if (fileUsesPattern($content, '/use\s+App\\\\Models\\\\/')
        && !fileUsesPattern($content, '/RepositoryInterface/')) {
        $violations['services_with_models_no_repository'][] = $rel;
    }
    if (fileUsesPattern($content, '/use\s+App\\\\Repositories\\\\(?!Contracts)/')
        && !fileUsesPattern($content, '/RepositoryInterface/')) {
        $violations['services_with_concrete_repository'][] = $rel;
    }
}

// 3. Repositories sem interface
$repoFiles = glob($basePath . '/app/Repositories/*Repository.php') ?: [];
foreach ($repoFiles as $file) {
    $basename = basename($file, '.php');
    $interfacePath = $basePath . '/app/Repositories/Contracts/' . $basename . 'Interface.php';
    if (!file_exists($interfacePath)) {
        $violations['repositories_without_interface'][] = relativePath($basePath, $file);
    }
}

// 4. Interfaces sem binding nos providers
$interfaceFiles = glob($basePath . '/app/Repositories/Contracts/*RepositoryInterface.php') ?: [];
$providerContent = '';
foreach (glob($basePath . '/app/Providers/*RepositoryServiceProvider.php') ?: [] as $provider) {
    $providerContent .= file_get_contents($provider);
}

$infrastructureInterfaces = [
    'BaseRepositoryInterface',
    'PaginateRepositoryInterface',
    'DetailPlanRepositoryInterface',
];

foreach ($interfaceFiles as $file) {
    $interfaceName = basename($file, '.php');
    if (in_array($interfaceName, $infrastructureInterfaces, true)) {
        continue;
    }
    if (!str_contains($providerContent, $interfaceName)) {
        $violations['interfaces_without_binding'][] = relativePath($basePath, $file);
    }
}

$sections = [
    'controllers_with_models' => 'Controllers API importando App\\Models',
    'controllers_with_db' => 'Controllers API usando DB facade',
    'controllers_with_repository_no_service' => 'Controllers injetando Repository sem Service',
    'services_with_models_no_repository' => 'Services usando Models sem RepositoryInterface',
    'services_with_concrete_repository' => 'Services usando Repository concreto (sem interface)',
    'repositories_without_interface' => 'Repositories sem interface em Contracts/',
    'interfaces_without_binding' => 'Interfaces sem binding em *RepositoryServiceProvider',
];

$total = 0;
foreach ($sections as $key => $label) {
    $items = $violations[$key] ?? [];
    $count = count($items);
    $total += $count;
    echo "{$label}: {$count}\n";
    foreach ($items as $item) {
        echo "  - {$item}\n";
    }
    echo "\n";
}

echo "Total de violações: {$total}\n";
exit($total > 0 ? 1 : 0);
