<?php
$logPath = 'C:\\Users\\florencia mendez\\.gemini\\antigravity\\brain\\57c10868-8ca6-4a62-88c0-4f911e420775\\.system_generated\\logs\\transcript.jsonl';

if (!file_exists($logPath)) {
    die("El archivo de log no existe en $logPath\n");
}

$targetLine = 5147; // Probemos con esta línea primero

$handle = fopen($logPath, "r");
if (!$handle) {
    die("No se pudo abrir el archivo de log.\n");
}

$lineNum = 0;
while (($line = fgets($handle)) !== false) {
    $lineNum++;
    if ($lineNum == $targetLine) {
        $data = json_decode($line, true);
        if ($data === null) {
            die("Error decodificando la línea $targetLine\n");
        }
        
        echo "TIPO: " . $data['type'] . "\n";
        if (isset($data['tool_calls'])) {
            foreach ($data['tool_calls'] as $call) {
                echo "TOOL NAME: " . $call['name'] . "\n";
                if (isset($call['args']['CodeContent'])) {
                    echo "--- CONTENIDO ENCONTRADO (Tamaño: " . strlen($call['args']['CodeContent']) . " bytes) ---\n";
                    // Guardar contenido en un archivo para verlo mejor
                    file_put_contents('c:\\xampp\\htdocs\\vaspa\\scratch\\inicio_extraido_5147.php', $call['args']['CodeContent']);
                    echo "Guardado en scratch/inicio_extraido_5147.php\n";
                } else {
                    echo "No tiene CodeContent. Args:\n";
                    print_r($call['args']);
                }
            }
        }
        break;
    }
}
fclose($handle);
