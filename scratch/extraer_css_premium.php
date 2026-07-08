<?php
$logPath = 'C:\\Users\\florencia mendez\\.gemini\\antigravity\\brain\\57c10868-8ca6-4a62-88c0-4f911e420775\\.system_generated\\logs\\transcript.jsonl';

if (!file_exists($logPath)) {
    die("El archivo de log no existe en $logPath\n");
}

$handle = fopen($logPath, "r");
$lineNum = 0;
while (($line = fgets($handle)) !== false) {
    $lineNum++;
    if (strpos($line, 'jumbotron') !== false && strpos($line, 'gradient') !== false) {
        // Encontramos un paso interesante de modificación de CSS
        $data = json_decode($line, true);
        if ($data) {
            $type = $data['type'];
            if ($type === 'PLANNER_RESPONSE' || $type === 'CODE_ACTION') {
                if (isset($data['tool_calls'])) {
                    foreach ($data['tool_calls'] as $call) {
                        $name = $call['name'];
                        if ($name === 'write_to_file' || $name === 'replace_file_content') {
                            $target = isset($call['args']['TargetFile']) ? $call['args']['TargetFile'] : '';
                            $desc = isset($call['args']['Description']) ? $call['args']['Description'] : '';
                            $content = "";
                            if (isset($call['args']['CodeContent'])) {
                                $content = $call['args']['CodeContent'];
                            } elseif (isset($call['args']['ReplacementContent'])) {
                                $content = $call['args']['ReplacementContent'];
                            }
                            
                            // Mostrar si contiene estilos
                            if (strpos($content, 'jumbotron') !== false || strpos($content, 'stepper') !== false) {
                                echo "Línea: $lineNum | Tool: $name | Target: $target | Desc: $desc\n";
                                // Guardar este bloque en un archivo para análisis
                                file_put_contents("c:\\xampp\\htdocs\\vaspa\\scratch\\css_premium_linea_{$lineNum}.txt", $content);
                                echo "   Guardado fragmento en scratch/css_premium_linea_{$lineNum}.txt\n";
                            }
                        }
                    }
                }
            }
        }
    }
}
fclose($handle);
