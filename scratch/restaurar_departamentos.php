<?php
$rawContent = file_get_contents('c:\\xampp\\htdocs\\vaspa\\vista\\asignaturas.departamentos.php');

// Si empieza y termina con comillas, las quitamos e interpretamos los caracteres de escape
if (substr($rawContent, 0, 1) === '"' && substr($rawContent, -1) === '"') {
    // Es una cadena JSON codificada en una sola línea
    $decoded = json_decode($rawContent);
    if ($decoded) {
        file_put_contents('c:\\xampp\\htdocs\\vaspa\\scratch\\departamentos_recuperado.php', $decoded);
        echo "Decodificado exitosamente como JSON string. Guardado en scratch/departamentos_recuperado.php\n";
    } else {
        // Falló json_decode, intentemos stripcslashes
        $stripped = stripcslashes(substr($rawContent, 1, -1));
        file_put_contents('c:\\xampp\\htdocs\\vaspa\\scratch\\departamentos_recuperado.php', $stripped);
        echo "Decodificado mediante stripcslashes. Guardado en scratch/departamentos_recuperado.php\n";
    }
} else {
    // Si no tiene comillas directas pero tiene \n literales, podemos usar stripcslashes en todo
    $stripped = stripcslashes($rawContent);
    file_put_contents('c:\\xampp\\htdocs\\vaspa\\scratch\\departamentos_recuperado.php', $stripped);
    echo "Decodificado mediante stripcslashes (sin comillas directas). Guardado en scratch/departamentos_recuperado.php\n";
}
