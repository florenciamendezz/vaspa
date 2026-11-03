<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    die('Acceso denegado: Este script solo puede ejecutarse mediante la interfaz de línea de comandos (CLI).');
}
include_once 'modeloSistema/BDConexionSistema.Class.php';

$sql = "SELECT id FROM asignatura LIMIT 1";
$res = BDConexionSistema::getInstancia()->query($sql);

if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    echo "Valid ID: " . $row['id'];
} else {
    echo "No asignaturas found.";
}
?>
