<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

try {
    $db = BDConexionSistema::getInstancia();
    
    // 1. Agregar columna 'estado' a la tabla plan usando sintaxis estándar de MySQL sin IF NOT EXISTS
    $sqlAlter = "ALTER TABLE `plan` ADD COLUMN `estado` ENUM('borrador', 'vigente', 'cerrado') NOT NULL DEFAULT 'borrador'";
    try {
        $db->query($sqlAlter);
        echo "Columna 'estado' agregada exitosamente.\n";
    } catch (Exception $e) {
        // Si ya existe la columna, ignoramos el error
        echo "Columna ya existente u otro error: " . $e->getMessage() . "\n";
    }

    // 2. Establecer como 'vigente' los planes sin fecha de fin
    $sqlSetVigente = "UPDATE `plan` SET `estado` = 'vigente' WHERE `anio_fin` IS NULL";
    $db->query($sqlSetVigente);
    echo "Planes activos actualizados a 'vigente'.\n";

    // 3. Establecer como 'cerrado' los planes con fecha de fin
    $sqlSetCerrado = "UPDATE `plan` SET `estado` = 'cerrado' WHERE `anio_fin` IS NOT NULL";
    $db->query($sqlSetCerrado);
    echo "Planes pasados actualizados a 'cerrado'.\n";
    
} catch (Exception $e) {
    echo "Error ejecutando migración: " . $e->getMessage() . "\n";
}
