<?php
include_once 'modeloSistema/BDConexionSistema.Class.php';

$idAsignatura = 2138;
$newEmail = 'esstefaniamendez@gmail.com';

echo "<h2>Fixing Email for Subject $idAsignatura</h2>";

// 1. Find Professor ID for this subject
// Asignatura -> idProfesor
$sqlAsig = "SELECT idProfesor, nombre FROM asignatura WHERE id = $idAsignatura";
$resAsig = BDConexionSistema::getInstancia()->query($sqlAsig);

if ($resAsig && $resAsig->num_rows > 0) {
    $row = $resAsig->fetch_assoc();
    $idProfesor = $row['idProfesor'];
    $nombreAsignatura = $row['nombre'];
    echo "Asignatura: $nombreAsignatura<br>";
    echo "ID Profesor: $idProfesor<br>";
    
    // 2. Update Professor Email
    if ($idProfesor) {
        $sqlUpdate = "UPDATE profesor SET email = '$newEmail' WHERE id = $idProfesor";
        if (BDConexionSistema::getInstancia()->query($sqlUpdate)) {
            echo "Email actualizado correctamente a $newEmail para el profesor ID $idProfesor.<br>";
        } else {
            echo "Error al actualizar email: " . BDConexionSistema::getInstancia()->error . "<br>";
        }
        
        // Verify
        $sqlVerify = "SELECT * FROM profesor WHERE id = $idProfesor";
        $resVerify = BDConexionSistema::getInstancia()->query($sqlVerify);
        $rowVerify = $resVerify->fetch_assoc();
        echo "Verificación: " . $rowVerify['nombre'] . " " . $rowVerify['apellido'] . " - " . $rowVerify['email'] . "<br>";
        
    } else {
        echo "No hay profesor asignado a esta asignatura.<br>";
    }
} else {
    echo "Asignatura no encontrada.<br>";
}
?>
