<?php
$path = "C:\\Users\\florencia mendez\\.gemini\\antigravity\\brain\\57c10868-8ca6-4a62-88c0-4f911e420775\\.system_generated\\logs\\transcript.jsonl";

if (!file_exists($path)) {
    die("No existe el transcript.\n");
}

$file = fopen($path, "r");
$lineNumber = 0;

echo "=== DIAGNOSTICO DE ESCRITURAS DE MONITOREO.CIRCUITO.PHP ===\n";

while (($line = fgets($file)) !== false) {
    $lineNumber++;
    $data = json_decode($line, true);
    if ($data && isset($data['tool_calls'])) {
        foreach ($data['tool_calls'] as $tc) {
            $name = $tc['name'];
            $args = $tc['args'];
            $targetFile = isset($args['TargetFile']) ? $args['TargetFile'] : '';
            if (stripos($targetFile, 'monitoreo.circuito.php') !== false) {
                $len = isset($args['CodeContent']) ? strlen($args['CodeContent']) : (isset($args['ReplacementContent']) ? strlen($args['ReplacementContent']) : 0);
                echo "Línea: $lineNumber | Herramienta: $name | Largo contenido: $len | Desc: " . (isset($args['Description']) ? $args['Description'] : 'sin desc') . "\n";
            }
        }
    }
}
fclose($file);
?>
