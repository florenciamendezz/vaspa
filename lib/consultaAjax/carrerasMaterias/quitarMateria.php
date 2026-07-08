<?php
include_once '../../../modeloSistema/BDConexionSistema.Class.php';
include_once '../../../lib/ControlAcceso.Class.php';

if (!ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_CARRERAS)) {
    echo json_encode(['success' => false, 'error' => 'Permiso denegado.']);
    exit;
}

if (isset($_POST['codCarrera']) && isset($_POST['idAsignatura'])) {
    $codCarrera = $_POST['codCarrera'];
    $idAsignatura = $_POST['idAsignatura'];
    $db = BDConexionSistema::getInstancia();

    $query = "DELETE FROM carrera_asignatura 
              WHERE idCarrera = '{$codCarrera}' AND idAsignatura = '{$idAsignatura}'";
    if ($db->query($query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al eliminar en la base de datos: ' . $db->error]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Parámetros incompletos.']);
}
