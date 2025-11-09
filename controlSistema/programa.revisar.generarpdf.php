<?php
// Controlador de previsualización de programa (PDF)

// 1) Arrancá con buffering para atrapar cualquier salida accidental
ob_start();

include_once '../lib/ControlAcceso.Class.php';
ControlAcceso::requierePermiso(PermisosSistema::PERMISO_REVISAR_PROGRAMA);
require_once '../modeloSistema/MYPDF.php';

// Sanitizar id
$idPrograma = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    if (!$idPrograma) {
        throw new Exception('ID de programa inválido');
    }

    $pdf = new MYPDF($idPrograma);

    // 2) Antes de generar/salir el PDF, limpiá TODOS los buffers
    while (ob_get_level() > 0) { ob_end_clean(); }

    // 3) generarPDFprograma() debe llamar internamente a $pdf->Output(...)
    //    y no hacer "echo" ni imprimir HTML.
    $pdf->generarPDFprograma();
    exit;

} catch (Throwable $exc) {
    // Si hubo error, asegurate de no mandar basura binaria
    while (ob_get_level() > 0) { ob_end_clean(); }

    header('Content-Type: text/html; charset=UTF-8');
    http_response_code(500);
    echo '<div class="alert alert-danger" role="alert">
            Ocurri&oacute; un error al intentar previsualizar el Programa. (' . htmlspecialchars($exc->getMessage()) . ')
          </div>';
}
