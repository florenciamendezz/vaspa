<?php
include_once '../../../modeloSistema/BDConexionSistema.Class.php';
include_once '../../../lib/ControlAcceso.Class.php';

if (!ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_CARRERAS)) {
    echo json_encode([]);
    exit;
}

if (isset($_POST['codCarrera'])) {
    $codCarrera = $_POST['codCarrera'];
    $db = BDConexionSistema::getInstancia();

    $query = "SELECT id, nombre FROM asignatura 
              WHERE id NOT IN (
                  SELECT idAsignatura FROM carrera_asignatura WHERE idCarrera = '{$codCarrera}'
              )
              ORDER BY nombre ASC";
    $res = $db->query($query);
    $materias = [];
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $materias[] = $row;
        }
    }
    echo json_encode($materias);
} else {
    echo json_encode([]);
}
