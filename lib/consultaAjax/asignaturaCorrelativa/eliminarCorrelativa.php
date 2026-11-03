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
    $stmtInfo = $db->prepare("SELECT idAsignatura, idPlan FROM correlativa_de WHERE id = ? LIMIT 1");
    $targetMateria = '';
    if ($stmtInfo) {
        $stmtInfo->bind_param("i", $idCorrelativaDe);
        $stmtInfo->execute();
        $infoRes = $stmtInfo->get_result();
        if ($infoRes && $infoRow = $infoRes->fetch_assoc()) {
            $targetMateria = $infoRow['idAsignatura'];
        }
        $stmtInfo->close();
    }

    $stmtDelete = $db->prepare("DELETE FROM correlativa_de WHERE id = ?");
    if ($stmtDelete) {
        $stmtDelete->bind_param("i", $idCorrelativaDe);
        if ($stmtDelete->execute()) {
            $stmtDelete->close();

            // Verificar si la materia aún tiene correlativas en el plan
            if (!empty($targetMateria) && !empty($idPlan)) {
                $stmtCount = $db->prepare("SELECT COUNT(*) as cant FROM correlativa_de WHERE idPlan = ? AND idAsignatura = ?");
                $cant = 0;
                if ($stmtCount) {
                    $stmtCount->bind_param("ss", $idPlan, $targetMateria);
                    $stmtCount->execute();
                    $countRes = $stmtCount->get_result();
                    if ($countRes && $row = $countRes->fetch_assoc()) {
                        $cant = intval($row['cant']);
                    }
                    $stmtCount->close();
                }
                if ($cant === 0) {
                    $stmtUpd = $db->prepare("UPDATE plan_asignatura SET tieneCorrelativa = 0 WHERE idPlan = ? AND idAsignatura = ?");
                    if ($stmtUpd) {
                        $stmtUpd->bind_param("ss", $idPlan, $targetMateria);
                        $stmtUpd->execute();
                        $stmtUpd->close();
                    }
                }
            }

            echo json_encode(['status' => 'success', 'message' => 'Correlativa eliminada correctamente.']);
        } else {
            $err = $stmtDelete->error;
            $stmtDelete->close();
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar correlatividad: ' . $err]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al preparar sentencia de eliminación: ' . $db->error]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
