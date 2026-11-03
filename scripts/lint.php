<?php
/**
 * VASPA - Script de Validación de Sintaxis PHP (Linting)
 * Recorre recursivamente el código fuente del proyecto y ejecuta php -l sobre cada archivo .php
 */

$rootDir = realpath(__DIR__ . '/..');

$excludeDirs = [
    'vendor',
    'node_modules',
    '.git',
    '.agents',
    'test-results',
    'playwright-report',
    'fonts',
    'examples',
];

echo "Iniciando análisis de sintaxis PHP en: $rootDir\n";

$dirIterator = new RecursiveDirectoryIterator($rootDir, RecursiveDirectoryIterator::SKIP_DOTS);
$filterIterator = new RecursiveCallbackFilterIterator($dirIterator, function ($current, $key, $iterator) use ($excludeDirs) {
    if ($current->isDir()) {
        return !in_array($current->getFilename(), $excludeDirs, true);
    }
    return true;
});

$iterator = new RecursiveIteratorIterator($filterIterator);

$filesChecked = 0;
$errors = 0;
$failedFiles = [];

foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $filePath = $file->getRealPath();

    $filesChecked++;
    $output = [];
    $returnCode = 0;

    exec('php -l ' . escapeshellarg($filePath) . ' 2>&1', $output, $returnCode);

    if ($returnCode !== 0) {
        $errors++;
        $failedFiles[] = [
            'file' => str_replace($rootDir . DIRECTORY_SEPARATOR, '', $filePath),
            'error' => implode("\n", $output)
        ];
    }
}

echo "Total de archivos de la aplicación analizados: $filesChecked\n";

if ($errors > 0) {
    echo "ERROR: Se encontraron $errors archivos con errores de sintaxis:\n\n";
    foreach ($failedFiles as $fail) {
        echo "- " . $fail['file'] . ":\n  " . $fail['error'] . "\n\n";
    }
    exit(1);
}

echo "ÉXITO: 100% de los archivos PHP ($filesChecked archivos) superaron la verificación de sintaxis sin errores.\n";
exit(0);
