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
$nombreAsignatura = trim($_POST['nombreAsignatura'] ?? '');

if (empty($idPlan)) {
    echo json_encode(['status' => 'error', 'message' => 'El código de plan es requerido.']);
    exit;
}

$idPlanEsc = $db->real_escape_string($idPlan);

try {
    // Si viene nombreAsignatura pero no idAsignatura, buscamos o creamos al vuelo
    if (empty($idAsignatura) && !empty($nombreAsignatura)) {
        $nombreEsc = $db->real_escape_string($nombreAsignatura);
        
        // Verificar si ya existe una asignatura con exactamente ese nombre
        $resExiste = $db->query("SELECT id FROM asignatura WHERE LOWER(TRIM(nombre)) = LOWER(TRIM('{$nombreEsc}')) LIMIT 1");
        if ($resExiste && $resExiste->num_rows > 0) {
            $rowExiste = $resExiste->fetch_assoc();
            $idAsignatura = $rowExiste['id'];
        } else {
            // Generar nuevo código de 4 dígitos numérico
            $resMax = $db->query("SELECT MAX(CAST(id AS UNSIGNED)) AS max_id FROM asignatura WHERE id REGEXP '^[0-9]+$'");
            $maxId = 1;
            if ($resMax && $rowMax = $resMax->fetch_assoc()) {
                $maxId = intval($rowMax['max_id']) + 1;
            }
            $nuevoId = str_pad($maxId, 4, '0', STR_PAD_LEFT);
            
            // Insertar nueva asignatura
            $insertAsig = $db->query("INSERT INTO asignatura (id, nombre) VALUES ('{$nuevoId}', '{$nombreEsc}')");
            if (!$insertAsig) {
                echo json_encode(['status' => 'error', 'message' => 'Error al dar de alta la nueva asignatura: ' . $db->error]);
                exit;
            }
            $idAsignatura = $nuevoId;
        }
    }

    if (empty($idAsignatura)) {
        echo json_encode(['status' => 'error', 'message' => 'Debe seleccionar o ingresar una asignatura.']);
        exit;
    }

    $idAsigEsc = $db->real_escape_string($idAsignatura);

    // Verificar si ya está vinculada al plan
    $checkQuery = "SELECT * FROM plan_asignatura WHERE idPlan = '{$idPlanEsc}' AND idAsignatura = '{$idAsigEsc}'";
    $checkRes = $db->query($checkQuery);
    if ($checkRes && $checkRes->num_rows > 0) {
        echo json_encode(['status' => 'warning', 'message' => "La asignatura {$idAsignatura} ya pertenece a este plan."]);
        exit;
    }

    // Insertar en plan_asignatura
    $sqlInsert = "INSERT INTO plan_asignatura (idPlan, idAsignatura, tieneCorrelativa) VALUES ('{$idPlanEsc}', '{$idAsigEsc}', 0)";
    if ($db->query($sqlInsert)) {
        echo json_encode(['status' => 'success', 'message' => "Asignatura agregada correctamente al plan.", 'idAsignatura' => $idAsignatura]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al vincular la asignatura: ' . $db->error]);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
