<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once 'modeloSistema/BDConexionSistema.Class.php';

echo "DIAGNOSTICO START\n";

$email = 'esstefaniamendez@gmail.com';
$nombre = 'Florencia';
$apellido = 'Mendez';

// 1. Verificar Usuario
$sqlUsuario = "SELECT * FROM usuario WHERE email = '$email'";
$resUsuario = BDConexionSistema::getInstancia()->query($sqlUsuario);
echo "1. USUARIO (Email: $email):\n";
if ($resUsuario && $resUsuario->num_rows > 0) {
    while ($row = $resUsuario->fetch_assoc()) {
        echo "   ID: " . $row['id'] . " | Nombre: " . $row['nombre'] . " | Email: " . $row['email'] . "\n";
    }
} else {
    echo "   No encontrado.\n";
}

// 2. Verificar Profesor por Email
$sqlProfEmail = "SELECT * FROM profesor WHERE email = '$email'";
$resProfEmail = BDConexionSistema::getInstancia()->query($sqlProfEmail);
echo "2. PROFESOR (Email: $email):\n";
if ($resProfEmail && $resProfEmail->num_rows > 0) {
    while ($row = $resProfEmail->fetch_assoc()) {
        echo "   ID: " . $row['id'] . " | Nombre: " . $row['nombre'] . " " . $row['apellido'] . " | Email: " . $row['email'] . "\n";
    }
} else {
    echo "   No encontrado.\n";
}

// 3. Verificar Profesor por Nombre
$sqlProfName = "SELECT * FROM profesor WHERE nombre LIKE '%$nombre%' OR apellido LIKE '%$apellido%'";
$resProfName = BDConexionSistema::getInstancia()->query($sqlProfName);
echo "3. PROFESOR (Busqueda por Nombre: $nombre $apellido):\n";
if ($resProfName && $resProfName->num_rows > 0) {
    while ($row = $resProfName->fetch_assoc()) {
        echo "   ID: " . $row['id'] . " | Nombre: " . $row['nombre'] . " " . $row['apellido'] . " | Email: " . $row['email'] . "\n";
    }
} else {
    echo "   No encontrado.\n";
}
echo "DIAGNOSTICO END\n";
?>
