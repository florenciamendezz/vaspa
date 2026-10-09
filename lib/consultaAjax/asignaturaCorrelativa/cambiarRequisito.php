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
$nuevoRequisito = trim($_POST['nuevoRequisito'] ?? '');

if ($idCorrelativaDe <= 0 || !in_array($nuevoRequisito, ['Regular', 'Aprobada'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos para actualizar el requisito.']);
    exit;
}

$reqEsc = $db->real_escape_string($nuevoRequisito);

try {
    $sqlUpdate = "UPDATE correlativa_de SET requisito = '{$reqEsc}' WHERE id = {$idCorrelativaDe}";
    if ($db->query($sqlUpdate)) {
        echo json_encode(['status' => 'success', 'message' => "Requisito actualizado a '{$nuevoRequisito}' exitosamente."]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al actualizar requisito: ' . $db->error]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
