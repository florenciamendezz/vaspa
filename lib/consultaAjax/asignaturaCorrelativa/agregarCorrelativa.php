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

$idPlan = trim($_POST['idPlan'] ?? '');
$idAsignatura = trim($_POST['idAsignatura'] ?? '');
$idCorrelativa = trim($_POST['idCorrelativa'] ?? '');
$requisito = trim($_POST['requisito'] ?? 'Regular');
$tipo = trim($_POST['tipo'] ?? 'Precedente');

if (empty($idPlan) || empty($idAsignatura) || empty($idCorrelativa)) {
    echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
    exit;
}

if ($idAsignatura === $idCorrelativa) {
    echo json_encode(['status' => 'error', 'message' => 'Una asignatura no puede ser correlativa de sí misma.']);
    exit;
}

if (!in_array($requisito, ['Regular', 'Aprobada'])) {
    $requisito = 'Regular';
}

$idPlanEsc = $db->real_escape_string($idPlan);
$reqEsc = $db->real_escape_string($requisito);

if ($tipo === 'Precedente') {
    // La materia actual requiere a $idCorrelativa
    $idMateria = $idAsignatura;
    $idAnt = $idCorrelativa;
} else {
    // $idCorrelativa requiere a la materia actual
    $idMateria = $idCorrelativa;
    $idAnt = $idAsignatura;
}

try {
    // Verificar si ya existe exactamente esta regla
    $stmtCheck = $db->prepare("SELECT id, requisito FROM correlativa_de WHERE idPlan = ? AND idAsignatura = ? AND idAsignatura_Correlativa_Anterior = ? LIMIT 1");
    if ($stmtCheck) {
        $stmtCheck->bind_param("sss", $idPlan, $idMateria, $idAnt);
        $stmtCheck->execute();
        $checkRes = $stmtCheck->get_result();
        if ($checkRes && $checkRes->num_rows > 0) {
            $row = $checkRes->fetch_assoc();
            $stmtCheck->close();
            echo json_encode(['status' => 'warning', 'message' => "Esta regla de correlatividad ya se encuentra registrada (Requisito actual: {$row['requisito']})."]);
            exit;
        }
        $stmtCheck->close();
    }

    $stmtInsert = $db->prepare("INSERT INTO correlativa_de (requisito, idAsignatura, idAsignatura_Correlativa_Anterior, idPlan) VALUES (?, ?, ?, ?)");
    if ($stmtInsert) {
        $stmtInsert->bind_param("ssss", $requisito, $idMateria, $idAnt, $idPlan);
        if ($stmtInsert->execute()) {
            $stmtInsert->close();

            // Actualizar tieneCorrelativa en plan_asignatura
            $stmtUpd = $db->prepare("UPDATE plan_asignatura SET tieneCorrelativa = 1 WHERE idPlan = ? AND idAsignatura = ?");
            if ($stmtUpd) {
                $stmtUpd->bind_param("ss", $idPlan, $idMateria);
                $stmtUpd->execute();
                $stmtUpd->close();
            }

            echo json_encode(['status' => 'success', 'message' => 'Correlativa agregada exitosamente.']);
        } else {
            $err = $stmtInsert->error;
            $stmtInsert->close();
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar correlatividad: ' . $err]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al preparar sentencia de inserción: ' . $db->error]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
