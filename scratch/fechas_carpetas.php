<?php
$brainDir = 'C:\\Users\\florencia mendez\\.gemini\\antigravity\\brain';
$files = scandir($brainDir);
$results = [];

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    $path = $brainDir . DIRECTORY_SEPARATOR . $file;
    if (is_dir($path)) {
        $results[] = [
            'name' => $file,
            'mtime' => filemtime($path),
            'date' => date('Y-m-d H:i:s', filemtime($path))
        ];
    }
}

// Ordenar por fecha de modificación descendente
usort($results, function($a, $b) {
    return $b['mtime'] - $a['mtime'];
});

foreach ($results as $res) {
    echo "Carpeta: {$res['name']} | Modificada: {$res['date']}\n";
}
