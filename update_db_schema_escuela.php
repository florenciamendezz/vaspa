<?php
include_once 'modeloSistema/BDConexionSistema.Class.php';
include_once 'lib/Constantes.Class.php';

echo "<h2>Actualizando esquema de base de datos para Rol Director de Escuela...</h2>";

$db = BDConexionSistema::getInstancia();

// 1. Agregar columnas a tabla 'programa'
$sql = "SHOW COLUMNS FROM programa LIKE 'aprobadoEscuela'";
$res = $db->query($sql);
if ($res && $res->num_rows == 0) {
    $sqlAlter = "ALTER TABLE programa ADD COLUMN aprobadoEscuela INT(1) NULL DEFAULT NULL AFTER aprobadoDepto";
    if ($db->query($sqlAlter)) {
        echo "Columna 'aprobadoEscuela' agregada a tabla 'programa'.<br>";
    } else {
        echo "Error agregando columna 'aprobadoEscuela' a tabla 'programa': " . $db->error . "<br>";
    }
} else {
    echo "Columna 'aprobadoEscuela' ya existe en tabla 'programa'.<br>";
}

$sql = "SHOW COLUMNS FROM programa LIKE 'comentarioEscuela'";
$res = $db->query($sql);
if ($res && $res->num_rows == 0) {
    $sqlAlter = "ALTER TABLE programa ADD COLUMN comentarioEscuela TEXT NULL DEFAULT NULL AFTER comentarioDepto";
    if ($db->query($sqlAlter)) {
        echo "Columna 'comentarioEscuela' agregada a tabla 'programa'.<br>";
    } else {
        echo "Error agregando columna 'comentarioEscuela' a tabla 'programa': " . $db->error . "<br>";
    }
} else {
    echo "Columna 'comentarioEscuela' ya existe en tabla 'programa'.<br>";
}

// 2. Agregar columnas a tabla 'programa_pdf_detalle'
$sql = "SHOW COLUMNS FROM programa_pdf_detalle LIKE 'aprobado_escuela'";
$res = $db->query($sql);
if ($res && $res->num_rows == 0) {
    $sqlAlter = "ALTER TABLE programa_pdf_detalle ADD COLUMN aprobado_escuela INT(1) NULL DEFAULT NULL AFTER aprobado_depto";
    if ($db->query($sqlAlter)) {
        echo "Columna 'aprobado_escuela' agregada a tabla 'programa_pdf_detalle'.<br>";
    } else {
        echo "Error agregando columna 'aprobado_escuela' a tabla 'programa_pdf_detalle': " . $db->error . "<br>";
    }
} else {
    echo "Columna 'aprobado_escuela' ya existe en tabla 'programa_pdf_detalle'.<br>";
}

// 3. Insertar Rol "Director de Escuela"
// Asumimos que la tabla de roles está en la base de datos de usuarios definida en Constantes::BD_USERS
$bdUsers = Constantes::BD_USERS;

$sqlCheckRol = "SELECT id FROM {$bdUsers}.rol WHERE nombre = 'Director de Escuela'";
$resCheckRol = $db->query($sqlCheckRol);

if ($resCheckRol && $resCheckRol->num_rows == 0) {
    $sqlInsertRol = "INSERT INTO {$bdUsers}.rol (nombre) VALUES ('Director de Escuela')";
    if ($db->query($sqlInsertRol)) {
        $idRol = $db->insert_id;
        echo "Rol 'Director de Escuela' creado con ID: $idRol.<br>";
        
        // 4. Asignar permiso "Revisar Programa"
        // Primero buscamos el ID del permiso
        $sqlPermiso = "SELECT id FROM {$bdUsers}.permiso WHERE nombre = 'Revisar Programa'";
        $resPermiso = $db->query($sqlPermiso);
        if ($resPermiso && $resPermiso->num_rows > 0) {
            $rowPermiso = $resPermiso->fetch_assoc();
            $idPermiso = $rowPermiso['id'];
            
            $sqlRolPermiso = "INSERT INTO {$bdUsers}.rol_permiso (id_rol, id_permiso) VALUES ($idRol, $idPermiso)";
            if ($db->query($sqlRolPermiso)) {
                echo "Permiso 'Revisar Programa' asignado al rol 'Director de Escuela'.<br>";
            } else {
                echo "Error asignando permiso: " . $db->error . "<br>";
            }
        } else {
            echo "No se encontró el permiso 'Revisar Programa'.<br>";
        }
        
    } else {
        echo "Error creando rol 'Director de Escuela': " . $db->error . "<br>";
    }
} else {
    echo "Rol 'Director de Escuela' ya existe.<br>";
}

echo "<h3>Actualización finalizada.</h3>";
?>
