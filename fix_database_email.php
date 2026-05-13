<?php
include_once 'modeloSistema/BDConexionSistema.Class.php';

// EMAIL DE DESTINO PARA TESTING
// Recuperado de constantesMail.php: esstefaniamendez@gmail.com
$emailDestino = "esstefaniamendez@gmail.com";

echo "<h1>Actualizacion Masiva de Emails de Profesores</h1>";
echo "<p>Se actualizaran todos los emails de la tabla PROFESOR a: <b>$emailDestino</b></p>";

$query = "UPDATE PROFESOR SET email = '{$emailDestino}'";

try {
    $resultado = BDConexionSistema::getInstancia()->query($query);
    if ($resultado) {
        echo "<div style='color: green; font-weight: bold;'>Exito: Se actualizaron los emails correctamente.</div>";
        echo "<p>Filas afectadas: " . BDConexionSistema::getInstancia()->affected_rows . "</p>";
    } else {
        echo "<div style='color: red; font-weight: bold;'>Error: No se pudo actualizar.</div>";
    }
} catch (Exception $e) {
    echo "Excepcion: " . $e->getMessage();
}
?>
