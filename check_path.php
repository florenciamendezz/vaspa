<?php
include_once 'modeloSistema/BDConexionSistema.Class.php';

$sql = "SELECT id, ruta_archivo FROM programa_pdf_detalle WHERE ruta_archivo LIKE '%prg_1659_2025_1764624322.pdf%'";
$res = BDConexionSistema::getInstancia()->query($sql);

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        echo "ID: " . $row['id'] . " - Ruta: " . $row['ruta_archivo'] . "\n";
    }
} else {
    echo "No se encontró el registro en la BD.\n";
}
?>
