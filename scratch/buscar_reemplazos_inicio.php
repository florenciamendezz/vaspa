<?php
$logPath = 'C:\\Users\\florencia mendez\\.gemini\\antigravity\\brain\\57c10868-8ca6-4a62-88c0-4f911e420775\\.system_generated\\logs\\transcript.jsonl';

if (!file_exists($logPath)) {
    die("El archivo de log no existe en $logPath\n");
}

$handle = fopen($logPath, "r");
$lineNum = 0;
while (($line = fgets($handle)) !== false) {
    $lineNum++;
    if (strpos($line, 'inicio.php') !== false) {
        $data = json_decode($line, true);
        if ($data) {
            $type = $data['type'];
            if ($type === 'PLANNER_RESPONSE' || $type === 'CODE_ACTION') {
                if (isset($data['tool_calls'])) {
                    foreach ($data['tool_calls'] as $call) {
                        $name = $call['name'];
                        if ($name === 'write_to_file' || $name === 'replace_file_content') {
                            $target = isset($call['args']['TargetFile']) ? $call['args']['TargetFile'] : '';
                            $contentLen = 0;
                            if (isset($call['args']['CodeContent'])) {
                                $contentLen = strlen($call['args']['CodeContent']);
                            }
                            if (isset($call['args']['ReplacementContent'])) {
                                $contentLen = strlen($call['args']['ReplacementContent']);
                            }
                            echo "Línea: $lineNum | Tipo: $type | Tool: $name | Target: $target | Tamaño contenido: $contentLen\n";
                        }
                    }
                }
            }
        }
    }
}
fclose($handle);
