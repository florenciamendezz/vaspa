<?php
$lines = file('C:/Users/florencia mendez/.gemini/antigravity/brain/57c10868-8ca6-4a62-88c0-4f911e420775/.system_generated/logs/transcript.jsonl');
foreach ($lines as $line) {
    $data = json_decode($line, true);
    if ($data && isset($data['step_index']) && $data['step_index'] >= 3000 && $data['step_index'] <= 3626) {
        if (isset($data['tool_calls'])) {
            foreach ($data['tool_calls'] as $tc) {
                if (isset($tc['args']['TargetFile']) && (strpos($tc['args']['TargetFile'], 'revisar.programas.php') !== false || strpos($tc['args']['TargetFile'], 'inicio.php') !== false)) {
                    echo "Step: " . $data['step_index'] . " | Tool: " . $tc['name'] . " | Target: " . $tc['args']['TargetFile'] . "\n";
                }
            }
        }
    }
}
