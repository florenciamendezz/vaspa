<?php
$path = "C:\\Users\\florencia mendez\\.gemini\\antigravity\\brain\\57c10868-8ca6-4a62-88c0-4f911e420775\\.system_generated\\logs\\transcript.jsonl";

if (!file_exists($path)) {
    die("No existe el transcript en: $path\n");
}

$file = fopen($path, "r");
$lineNumber = 0;
$inicio_code = "";

echo "=== INICIANDO RECONSTRUCCION DE INICIO.PHP PREMIUM ===\n";

while (($line = fgets($file)) !== false) {
    $lineNumber++;
    $data = json_decode($line, true);
    if (!$data) continue;
    
    // Solo aplicar cambios del martes o anteriores (hasta la línea 4210 del transcript)
    if ($lineNumber > 4210) {
        break;
    }
    
    if (isset($data['tool_calls'])) {
        foreach ($data['tool_calls'] as $tc) {
            $name = $tc['name'];
            $args = $tc['args'];
            $targetFile = isset($args['TargetFile']) ? $args['TargetFile'] : '';
            
            if (stripos($targetFile, 'inicio.php') !== false) {
                if ($name == 'write_to_file') {
                    $inicio_code = $args['CodeContent'];
                    echo "Línea $lineNumber: Escribiendo inicio.php inicial (largo: " . strlen($inicio_code) . ")\n";
                } elseif ($name == 'replace_file_content') {
                    $target = $args['TargetContent'];
                    $replacement = $args['ReplacementContent'];
                    
                    // Aplicar reemplazo
                    $pos = strpos($inicio_code, $target);
                    if ($pos !== false) {
                        $inicio_code = substr_replace($inicio_code, $replacement, $pos, strlen($target));
                        echo "Línea $lineNumber: Reemplazo exitoso (replace_file_content)\n";
                    } else {
                        echo "Línea $lineNumber: [ADVERTENCIA] No se encontró el TargetContent para reemplazar.\n";
                    }
                } elseif ($name == 'multi_replace_file_content') {
                    $chunks = $args['ReplacementChunks'];
                    if (is_string($chunks)) {
                        $chunks = json_decode($chunks, true);
                    }
                    
                    if (is_array($chunks)) {
                        echo "Línea $lineNumber: Procesando multi_replace_file_content con " . count($chunks) . " chunks\n";
                        foreach ($chunks as $chunk) {
                            $target = $chunk['TargetContent'];
                            $replacement = $chunk['ReplacementContent'];
                            $pos = strpos($inicio_code, $target);
                            if ($pos !== false) {
                                $inicio_code = substr_replace($inicio_code, $replacement, $pos, strlen($target));
                                echo "   Chunk reemplazado con éxito\n";
                            } else {
                                echo "   [ADVERTENCIA] Chunk no encontrado en el código actual.\n";
                            }
                        }
                    } else {
                        echo "Línea $lineNumber: [ERROR] ReplacementChunks no es un array válido.\n";
                    }
                }
            }
        }
    }
}
fclose($file);

if (!empty($inicio_code)) {
    // Guardar el inicio.php premium en la carpeta correspondiente
    file_put_contents("c:/xampp/htdocs/vaspa/vista/inicio.php", $inicio_code);
    echo "\n[OK] inicio.php premium reconstruido con éxito (largo: " . strlen($inicio_code) . " bytes).\n";
} else {
    echo "\n[ERROR] No se pudo reconstruir inicio.php premium.\n";
}

?>
