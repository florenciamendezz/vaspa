<?php
$logPath = 'C:\\Users\\florencia mendez\\.gemini\\antigravity\\brain\\57c10868-8ca6-4a62-88c0-4f911e420775\\.system_generated\\logs\\transcript.jsonl';

if (!file_exists($logPath)) {
    die("El archivo de log no existe en $logPath\n");
}

// Vamos a buscar la línea 4770 y líneas cercanas, e imprimir su estructura
$handle = fopen($logPath, "r");
$lineNum = 0;
while (($line = fgets($handle)) !== false) {
    $lineNum++;
    if ($lineNum >= 4768 && $lineNum <= 4775) {
        $data = json_decode($line, true);
        if ($data) {
            echo "--- LÍNEA $lineNum (Tipo: {$data['type']}) ---\n";
            if (isset($data['tool_calls'])) {
                foreach ($data['tool_calls'] as $call) {
                    echo "Tool: {$call['name']}\n";
                    if (isset($call['args']['TargetFile'])) {
                        echo "TargetFile: {$call['args']['TargetFile']}\n";
                    }
                    // Imprimir fragmentos de las propiedades
                    if (isset($call['args']['CodeContent'])) {
                        echo "CodeContent size: " . strlen($call['args']['CodeContent']) . "\n";
                    }
                    if (isset($call['args']['ReplacementContent'])) {
                        echo "ReplacementContent snippet: " . substr($call['args']['ReplacementContent'], 0, 1000) . "\n";
                    }
                }
            }
            if (isset($data['content'])) {
                echo "Content snippet: " . substr($data['content'], 0, 500) . "\n";
            }
        } else {
            echo "Línea $lineNum no es JSON válido.\n";
        }
    }
}
fclose($handle);
