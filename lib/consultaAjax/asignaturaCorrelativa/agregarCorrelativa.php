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
    $idMateria = $db->real_escape_string($idAsignatura);
    $idAnt = $db->real_escape_string($idCorrelativa);
} else {
    // $idCorrelativa requiere a la materia actual
    $idMateria = $db->real_escape_string($idCorrelativa);
    $idAnt = $db->real_escape_string($idAsignatura);
}

try {
    // Verificar si ya existe exactamente esta regla
    $checkSql = "SELECT id, requisito FROM correlativa_de WHERE idPlan = '{$idPlanEsc}' AND idAsignatura = '{$idMateria}' AND idAsignatura_Correlativa_Anterior = '{$idAnt}'";
    $checkRes = $db->query($checkSql);
    if ($checkRes && $checkRes->num_rows > 0) {
        $row = $checkRes->fetch_assoc();
        echo json_encode(['status' => 'warning', 'message' => "Esta regla de correlatividad ya se encuentra registrada (Requisito actual: {$row['requisito']})."]);
        exit;
    }

    $sqlInsert = "INSERT INTO correlativa_de (requisito, idAsignatura, idAsignatura_Correlativa_Anterior, idPlan) VALUES ('{$reqEsc}', '{$idMateria}', '{$idAnt}', '{$idPlanEsc}')";
    if ($db->query($sqlInsert)) {
        // Actualizar tieneCorrelativa en plan_asignatura
        $db->query("UPDATE plan_asignatura SET tieneCorrelativa = 1 WHERE idPlan = '{$idPlanEsc}' AND idAsignatura = '{$idMateria}'");

        echo json_encode(['status' => 'success', 'message' => 'Correlativa agregada exitosamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar correlatividad: ' . $db->error]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
