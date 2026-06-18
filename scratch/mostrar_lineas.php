<?php
$content = file_get_contents('c:\\xampp\\htdocs\\vaspa\\scratch\\inicio_extraido_5147.php');
echo "Tamaño del archivo extraído: " . strlen($content) . " bytes\n";

$lines = explode("\n", $content);
echo "Total de líneas reales: " . count($lines) . "\n";

echo "--- Primeras 100 líneas ---\n";
for ($i = 0; $i < min(100, count($lines)); $i++) {
    echo ($i+1) . ": " . $lines[$i] . "\n";
}
