<?php
include_once '../lib/ControlAcceso.Class.php';
require_once '../modeloSistema/BDConexionSistema.Class.php';

// Cabecera JSON
header('Content-Type: application/json; charset=UTF-8');

// Validar que el usuario tenga rol de Administrador o Vinculación Académica
$usuario = $_SESSION['usuario'];
$rol = $usuario->roles[0]->nombre;
if ($rol != PermisosSistema::ROL_ADMIN && $rol != PermisosSistema::ROL_VINCULACION_ACADEMICA) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

// Validar que la petición sea POST y que vengan los parámetros requeridos
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

$idAsignatura = isset($_POST['idAsignatura']) ? trim($_POST['idAsignatura']) : null;
$idDepartamento = isset($_POST['idDepartamento']) ? intval($_POST['idDepartamento']) : null;

if (empty($idAsignatura) || empty($idDepartamento)) {
    echo json_encode(['success' => false, 'error' => 'Parámetros requeridos faltantes']);
    exit();
}

try {
    $db = BDConexionSistema::getInstancia();
    
    // Iniciar transacción
    $db->autocommit(FALSE);
    
    $idAsignaturaEscaped = $db->real_escape_string($idAsignatura);
    $idDepartamentoEscaped = intval($idDepartamento);
    
    // Actualizar únicamente idDepartamento para preservar la escuela original
    $sql = "UPDATE asignatura 
            SET idDepartamento = {$idDepartamentoEscaped} 
            WHERE id = '{$idAsignaturaEscaped}'";
            
    if ($db->query($sql)) {
        if ($db->affected_rows >= 0) {
            $db->commit();
            $db->autocommit(TRUE);
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("No se encontró la asignatura especificada.");
        }
    } else {
        throw new Exception("Error al actualizar la base de datos: " . $db->error);
    }
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollback();
        $db->autocommit(TRUE);
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
