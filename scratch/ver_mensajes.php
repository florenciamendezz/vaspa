<?php
$transcriptPath = 'C:/Users/florencia mendez/.gemini/antigravity/brain/57c10868-8ca6-4a62-88c0-4f911e420775/.system_generated/logs/transcript.jsonl';
$lines = file($transcriptPath);

echo "Historial de mensajes de hoy:\n";
foreach ($lines as $index => $line) {
    $data = json_decode($line, true);
    if (!$data) continue;
    
    if (isset($data['created_at']) && strpos($data['created_at'], '2026-05-28') !== false) {
        $step = $data['step_index'];
        $source = $data['source'];
        $type = $data['type'];
        $content = $data['content'] ?? '';
        
        if ($type === 'USER_INPUT') {
            echo "--------------------------------------------------\n";
            echo "PASO {$step} | USUARIO:\n{$content}\n";
        } elseif ($type === 'PLANNER_RESPONSE' || ($source === 'MODEL' && $type === 'TEXT')) {
            // Limitar longitud para no inundar la consola
            $snippet = mb_substr($content, 0, 500);
            if (mb_strlen($content) > 500) $snippet .= "... (truncado)";
            echo "PASO {$step} | MODELO:\n{$snippet}\n";
        }
    }
}
?>
