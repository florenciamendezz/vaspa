<?php
$logPath = 'C:\\Users\\florencia mendez\\.gemini\\antigravity\\brain\\57c10868-8ca6-4a62-88c0-4f911e420775\\.system_generated\\logs\\transcript.jsonl';

if (!file_exists($logPath)) {
    die("El archivo de log no existe en $logPath\n");
}

$handle = fopen($logPath, "r");
$lineNum = 0;
while (($line = fgets($handle)) !== false) {
    $lineNum++;
    if (stripos($line, 'HOME_INICIO') !== false) {
        echo "Línea $lineNum menciona HOME_INICIO.\n";
        // Mostrar fragmentos alrededor de la palabra HOME_INICIO
        $pos = stripos($line, 'HOME_INICIO');
        $start = max(0, $pos - 100);
        $len = 200;
        echo "Contexto: " . substr($line, $start, $len) . "\n\n";
    }
}
fclose($handle);
