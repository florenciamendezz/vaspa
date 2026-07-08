<?php
$logPath = 'C:\\Users\\florencia mendez\\.gemini\\antigravity\\brain\\57c10868-8ca6-4a62-88c0-4f911e420775\\.system_generated\\logs\\transcript.jsonl';

if (!file_exists($logPath)) {
    die("El archivo de log no existe en $logPath\n");
}

$handle = fopen($logPath, "r");
$lineNum = 0;
while (($line = fgets($handle)) !== false) {
    $lineNum++;
    if ($lineNum == 4881) {
        $data = json_decode($line, true);
        if ($data) {
            foreach ($data['tool_calls'] as $call) {
                if (isset($call['args']['TargetContent'])) {
                    // Guardar el TargetContent que es el código premium que se reemplazó
                    $premiumCode = $call['args']['TargetContent'];
                    file_put_contents('c:\\xampp\\htdocs\\vaspa\\scratch\\inicio_premium_recuperado.php', $premiumCode);
                    echo "Código premium original recuperado y guardado en scratch/inicio_premium_recuperado.php\n";
                } else {
                    echo "No se encontró TargetContent en los argumentos.\n";
                }
            }
        } else {
            echo "Error al decodificar JSON de la línea 4881.\n";
        }
        break;
    }
}
fclose($handle);
