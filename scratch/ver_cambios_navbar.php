<?php
$transcriptPath = 'C:/Users/florencia mendez/.gemini/antigravity/brain/57c10868-8ca6-4a62-88c0-4f911e420775/.system_generated/logs/transcript.jsonl';
$lines = file($transcriptPath);

echo "Buscando cambios específicos en navbar.php y revisar.programas.php:\n";
foreach ($lines as $index => $line) {
    $data = json_decode($line, true);
    if (!$data) continue;
    
    if (isset($data['created_at']) && strpos($data['created_at'], '2026-05-28') !== false) {
        $step = $data['step_index'];
        $source = $data['source'];
        $type = $data['type'];
        
        if ($source === 'MODEL' && isset($data['tool_calls'])) {
            foreach ($data['tool_calls'] as $tc) {
                $name = $tc['name'];
                $args = $tc['args'];
                $file = is_string($args) ? $args : ($args['TargetFile'] ?? '');
                
                if ((strpos($file, 'navbar.php') !== false || strpos($file, 'revisar.programas.php') !== false) && 
                    ($name === 'replace_file_content' || $name === 'write_to_file' || $name === 'multi_replace_file_content')) {
                    echo "PASO {$step} | Herramienta: {$name} | Archivo: {$file}\n";
                    echo "Instruction: " . ($args['Instruction'] ?? '') . "\n";
                    echo "ReplacementContent: " . ($args['ReplacementContent'] ?? '') . "\n";
                    echo "--------------------------------------------------\n";
                }
            }
        }
    }
}
?>
