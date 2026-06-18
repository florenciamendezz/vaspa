<?php
$logPath = 'C:\\Users\\florencia mendez\\.gemini\\antigravity\\brain\\57c10868-8ca6-4a62-88c0-4f911e420775\\.system_generated\\logs\\transcript.jsonl';

if (!file_exists($logPath)) {
    die("El archivo de log no existe en $logPath\n");
}

$handle = fopen($logPath, "r");
if (!$handle) {
    die("No se pudo abrir el archivo de log.\n");
}

$lineNum = 0;
while (($line = fgets($handle)) !== false) {
    $lineNum++;
    if (strpos($line, 'inicio.php') !== false) {
        // Encontramos una línea que menciona inicio.php
        // Intentemos decodificarla como JSON
        $data = json_decode($line, true);
        if ($data === null) {
            echo "Línea $lineNum menciona inicio.php pero no es JSON válido o falló decodificación.\n";
            continue;
        }
        
        $type = isset($data['type']) ? $data['type'] : 'unknown';
        $toolCalls = isset($data['tool_calls']) ? $data['tool_calls'] : null;
        
        echo "Línea $lineNum: Tipo: $type";
        if ($toolCalls) {
            foreach ($toolCalls as $call) {
                $name = isset($call['name']) ? $call['name'] : 'unknown';
                $args = isset($call['args']) ? $call['args'] : [];
                $targetFile = isset($args['TargetFile']) ? $args['TargetFile'] : '';
                echo " | Tool: $name | Target: $targetFile";
                if ($name === 'write_to_file' || $name === 'replace_file_content' || $name === 'multi_replace_file_content') {
                    echo " (MODIFICACIÓN)";
                }
            }
        }
        echo "\n";
    }
}
fclose($handle);
