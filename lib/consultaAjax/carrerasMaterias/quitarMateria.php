<?php
include_once '../../../modeloSistema/BDConexionSistema.Class.php';
include_once '../../../lib/ControlAcceso.Class.php';

if (!ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_CARRERAS)) {
    echo json_encode(['success' => false, 'error' => 'Permiso denegado.']);
    exit;
}

if (isset($_POST['codCarrera']) && isset($_POST['idAsignatura'])) {
    $codCarrera = trim($_POST['codCarrera']);
    $idAsignatura = trim($_POST['idAsignatura']);
    $db = BDConexionSistema::getInstancia();

    $stmt = $db->prepare("DELETE FROM carrera_asignatura WHERE idCarrera = ? AND idAsignatura = ?");
    if ($stmt) {
        $stmt->bind_param("ss", $codCarrera, $idAsignatura);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al eliminar en la base de datos: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al preparar sentencia: ' . $db->error]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Parámetros incompletos.']);
}
