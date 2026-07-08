<?php
include_once '../../../modeloSistema/BDConexionSistema.Class.php';
include_once '../../../lib/ControlAcceso.Class.php';

if (!ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_CARRERAS)) {
    echo json_encode(['success' => false, 'error' => 'Permiso denegado.']);
    exit;
}

if (isset($_POST['codCarrera']) && isset($_POST['idAsignatura']) && isset($_POST['activo'])) {
    $codCarrera = $_POST['codCarrera'];
    $idAsignatura = $_POST['idAsignatura'];
    $activo = intval($_POST['activo']) === 1 ? "b'1'" : "b'0'";
    
    $db = BDConexionSistema::getInstancia();

    $query = "UPDATE carrera_asignatura SET activo = {$activo} 
              WHERE idCarrera = '{$codCarrera}' AND idAsignatura = '{$idAsignatura}'";
    if ($db->query($query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al actualizar en la base de datos: ' . $db->error]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Parámetros incompletos.']);
}
