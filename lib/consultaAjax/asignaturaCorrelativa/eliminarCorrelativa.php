<?php
header('Content-Type: application/json; charset=utf-8');
include_once '../../../lib/ControlAcceso.Class.php';
include_once '../../../modeloSistema/BDConexionSistema.Class.php';

$UsuarioSes = $_SESSION['usuario'] ?? null;
$perfil = "";
if (isset($UsuarioSes->roles[0])) {
    $perfil = $UsuarioSes->roles[0]->nombre;
}

if (!ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_ASIGNATURAS, $UsuarioSes) && $perfil != PermisosSistema::ROL_VINCULACION_ACADEMICA) {
    echo json_encode(['status' => 'error', 'message' => 'No tiene permisos para realizar esta operación.']);
    exit;
}

$db = BDConexionSistema::getInstancia();

$idCorrelativaDe = intval($_POST['id'] ?? 0);
$idPlan = trim($_POST['idPlan'] ?? '');
$idAsignatura = trim($_POST['idAsignatura'] ?? '');

if ($idCorrelativaDe <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Identificador de correlatividad inválido.']);
    exit;
}

$idPlanEsc = $db->real_escape_string($idPlan);
$idAsigEsc = $db->real_escape_string($idAsignatura);

try {
    // Obtener datos antes de borrar para verificar y actualizar tieneCorrelativa
    $infoSql = "SELECT idAsignatura, idPlan FROM correlativa_de WHERE id = {$idCorrelativaDe}";
    $infoRes = $db->query($infoSql);
    $targetMateria = '';
    if ($infoRes && $infoRow = $infoRes->fetch_assoc()) {
        $targetMateria = $infoRow['idAsignatura'];
    }

    $sqlDelete = "DELETE FROM correlativa_de WHERE id = {$idCorrelativaDe}";
    if ($db->query($sqlDelete)) {
        // Verificar si la materia aún tiene correlativas en el plan
        if (!empty($targetMateria) && !empty($idPlanEsc)) {
            $countSql = "SELECT COUNT(*) as cant FROM correlativa_de WHERE idPlan = '{$idPlanEsc}' AND idAsignatura = '{$targetMateria}'";
            $countRes = $db->query($countSql);
            $cant = 0;
            if ($countRes && $row = $countRes->fetch_assoc()) {
                $cant = intval($row['cant']);
            }
            if ($cant === 0) {
                $db->query("UPDATE plan_asignatura SET tieneCorrelativa = 0 WHERE idPlan = '{$idPlanEsc}' AND idAsignatura = '{$targetMateria}'");
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'Correlativa eliminada correctamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al eliminar correlatividad: ' . $db->error]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
