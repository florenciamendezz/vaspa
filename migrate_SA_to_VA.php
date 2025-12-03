<?php
include_once 'lib/ControlAcceso.Class.php';
include_once 'modeloSistema/BDConexionSistema.Class.php';

$db = BDConexionSistema::getInstancia();

echo "Iniciando migración de SA a VA...\n";

// 1. Alterar tabla programa
echo "Modificando tabla programa...\n";

// Renombrar columnas
$sql1 = "ALTER TABLE programa CHANGE aprobadoSa aprobadoVa bit DEFAULT NULL";
if ($db->query($sql1)) echo " - Columna aprobadoSa renombrada a aprobadoVa.\n";
else echo " - Error al renombrar aprobadoSa: " . $db->error . "\n";

$sql2 = "ALTER TABLE programa CHANGE comentarioSa comentarioVa text DEFAULT NULL";
if ($db->query($sql2)) echo " - Columna comentarioSa renombrada a comentarioVa.\n";
else echo " - Error al renombrar comentarioSa: " . $db->error . "\n";

// Actualizar enum ubicacion
// Primero ampliamos el enum para incluir VA
$sql3 = "ALTER TABLE programa MODIFY COLUMN ubicacion enum('SA','DPTO','VA') DEFAULT NULL";
if ($db->query($sql3)) echo " - Enum ubicacion ampliado.\n";
else echo " - Error al ampliar enum ubicacion: " . $db->error . "\n";

// Actualizamos los datos
$sql4 = "UPDATE programa SET ubicacion = 'VA' WHERE ubicacion = 'SA'";
if ($db->query($sql4)) echo " - Datos de ubicacion actualizados de SA a VA.\n";
else echo " - Error al actualizar datos de ubicacion: " . $db->error . "\n";

// Reducimos el enum (opcional, pero limpio)
$sql5 = "ALTER TABLE programa MODIFY COLUMN ubicacion enum('VA','DPTO') DEFAULT NULL";
if ($db->query($sql5)) echo " - Enum ubicacion limpiado.\n";
else echo " - Error al limpiar enum ubicacion: " . $db->error . "\n";


// 2. Alterar tabla programa_pdf_detalle
echo "Modificando tabla programa_pdf_detalle...\n";

// Verificar si la tabla existe (por si acaso)
$sqlCheck = "SHOW TABLES LIKE 'programa_pdf_detalle'";
$res = $db->query($sqlCheck);
if ($res && $res->num_rows > 0) {
    $sql6 = "ALTER TABLE programa_pdf_detalle CHANGE aprobado_sa aprobado_va bit DEFAULT NULL";
    if ($db->query($sql6)) echo " - Columna aprobado_sa renombrada a aprobado_va.\n";
    else echo " - Error al renombrar aprobado_sa: " . $db->error . "\n";
} else {
    echo " - La tabla programa_pdf_detalle no existe.\n";
}

echo "Migración completada.\n";
?>
