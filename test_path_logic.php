<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once 'controlSistema/ManejadorProgramaPDF.php';
include_once 'modeloSistema/BDConexionSistema.Class.php';

echo "Buscando programas aprobados por los 3 roles...\n";
$sql = "SELECT * FROM programa_pdf_detalle WHERE aprobado_sa=1 AND aprobado_depto=1 AND aprobado_escuela=1 LIMIT 1";
$res = BDConexionSistema::getInstancia()->query($sql);

if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $anio = $row['anio'];
    $idAsignatura = $row['id_asignatura'];
    echo "Encontrado programa aprobado: ID={$row['id']}, Anio={$anio}, Asignatura={$idAsignatura}\n";
    
    // Instanciar Manejador
    $codCarrera = "000"; 
    
    $manejador = new ManejadorProgramaPDF($codCarrera, $anio);
    $coleccion = $manejador->getColeccion();
    
    $ruta = $manejador->tieneProgramaPDF($idAsignatura);
    if ($ruta) {
        echo "Ruta generada: $ruta\n";
        if (strpos($ruta, "../archivos/programas/") !== false) {
             echo "EXITO: La ruta contiene la carpeta correcta.\n";
        } else {
             echo "FALLO: La ruta NO contiene la carpeta correcta.\n";
        }
    } else {
        echo "FALLO: tieneProgramaPDF devolvio vacio.\n";
    }
} else {
    echo "No se encontraron programas aprobados por los 3 roles en la BD para probar.\n";
}
?>
