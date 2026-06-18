<?php
$path = "C:\\Users\\florencia mendez\\.gemini\\antigravity\\brain\\57c10868-8ca6-4a62-88c0-4f911e420775\\.system_generated\\logs\\transcript.jsonl";

if (!file_exists($path)) {
    die("No existe el transcript.\n");
}

$file = fopen($path, "r");
$lineNumber = 0;

while (($line = fgets($file)) !== false) {
    $lineNumber++;
    if ($lineNumber == 2706) {
        $data = json_decode($line, true);
        if ($data) {
            $code = $data['tool_calls'][0]['args']['CodeContent'];
            echo "Largo total del código: " . strlen($code) . " bytes\n";
            echo "--- INICIO DE CADENA ---\n";
            echo substr($code, 0, 300) . "\n";
            echo "--- FIN DE CADENA ---\n";
            echo substr($code, -300) . "\n";
        } else {
            echo "Error al decodificar JSON en la línea 2706\n";
        }
        break;
    }
}
fclose($file);
?>
