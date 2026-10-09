<?php
header('Content-Type: application/json; charset=utf-8');
include_once '../../../lib/ControlAcceso.Class.php';
include_once '../../../modeloSistema/BDConexionSistema.Class.php';

$UsuarioSes = $_SESSION['usuario'] ?? null;
$perfil = "";
if (isset($UsuarioSes->roles[0])) {
    $perfil = $UsuarioSes->roles[0]->nombre;
}

if (!ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_CARRERAS, $UsuarioSes) && $perfil != PermisosSistema::ROL_VINCULACION_ACADEMICA) {
    echo json_encode(['status' => 'error', 'message' => 'No tiene permisos para realizar esta operación.']);
    exit;
}

$db = BDConexionSistema::getInstancia();

$idPlan = trim($_POST['idPlan'] ?? '');
$idAsignatura = trim($_POST['idAsignatura'] ?? '');

if (empty($idPlan) || empty($idAsignatura)) {
    echo json_encode(['status' => 'error', 'message' => 'El código de plan y de asignatura son requeridos.']);
    exit;
}

$idPlanEsc = $db->real_escape_string($idPlan);
$idAsigEsc = $db->real_escape_string($idAsignatura);

try {
    // 1. Eliminar reglas de correlatividad asociadas a este plan y asignatura
    $db->query("DELETE FROM correlativa_de WHERE idPlan = '{$idPlanEsc}' AND (idAsignatura = '{$idAsigEsc}' OR idAsignatura_Correlativa_Anterior = '{$idAsigEsc}')");

    // 2. Eliminar de plan_asignatura
    $sqlDelete = "DELETE FROM plan_asignatura WHERE idPlan = '{$idPlanEsc}' AND idAsignatura = '{$idAsigEsc}'";
    if ($db->query($sqlDelete)) {
        echo json_encode(['status' => 'success', 'message' => 'Asignatura desvinculada del plan correctamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al desvincular la asignatura: ' . $db->error]);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
