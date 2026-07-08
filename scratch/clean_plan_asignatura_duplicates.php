<?php
require_once 'c:/xampp/htdocs/vaspa/modeloSistema/BDConexionSistema.Class.php';

try {
    $db = BDConexionSistema::getInstancia();
    
    echo "Iniciando limpieza de duplicados en plan_asignatura...\n";
    
    // Obtener la cantidad de filas antes de la limpieza
    $resBefore = $db->query("SELECT COUNT(*) as total FROM plan_asignatura");
    $before = $resBefore->fetch_assoc()['total'];
    echo "Registros antes de la limpieza: {$before}\n";
    
    // Ejecutar consultas de limpieza mediante tabla temporal
    $db->query("CREATE TABLE plan_asignatura_temp AS SELECT DISTINCT * FROM plan_asignatura");
    $db->query("TRUNCATE TABLE plan_asignatura");
    $db->query("INSERT INTO plan_asignatura SELECT * FROM plan_asignatura_temp");
    $db->query("DROP TABLE plan_asignatura_temp");
    
    // Obtener la cantidad de filas después de la limpieza
    $resAfter = $db->query("SELECT COUNT(*) as total FROM plan_asignatura");
    $after = $resAfter->fetch_assoc()['total'];
    echo "Registros después de la limpieza: {$after}\n";
    
    $eliminados = $before - $after;
    echo "Limpieza completada. Se eliminaron {$eliminados} registros duplicados.\n";
    
} catch (Exception $e) {
    echo "Error durante la limpieza: " . $e->getMessage() . "\n";
}
?>
