<?php
include_once 'modeloSistema/BDConexionSistema.Class.php';

$sql = "CREATE TABLE IF NOT EXISTS log_informes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    email_usuario VARCHAR(255),
    fecha_hora DATETIME,
    tipo_informe VARCHAR(100),
    contenido MEDIUMTEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if (BDConexionSistema::getInstancia()->query($sql)) {
    echo "Table 'log_informes' created successfully.";
} else {
    echo "Error creating table: " . BDConexionSistema::getInstancia()->error;
}
?>
