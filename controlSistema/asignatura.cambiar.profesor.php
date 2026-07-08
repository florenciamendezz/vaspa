<?php
include_once '../lib/ControlAcceso.Class.php';
require_once '../modeloSistema/BDConexionSistema.Class.php';

// Cabecera JSON
header('Content-Type: application/json; charset=UTF-8');

// Validar que el usuario tenga rol de Administrador o Vinculación Académica
$usuario = $_SESSION['usuario'];
$rol = (isset($usuario->roles) && is_array($usuario->roles) && count($usuario->roles) > 0) ? $usuario->roles[0]->nombre : '';
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
$idsProfesores = isset($_POST['idsProfesores']) && is_array($_POST['idsProfesores']) ? $_POST['idsProfesores'] : [];

if (empty($idAsignatura)) {
    echo json_encode(['success' => false, 'error' => 'Parámetros requeridos faltantes']);
    exit();
}

try {
    $db = BDConexionSistema::getInstancia();
    
    // Iniciar transacción
    $db->autocommit(FALSE);
    
    $idAsignaturaEscaped = $db->real_escape_string($idAsignatura);
    
    // Eliminar responsables previos
    $sqlDelete = "DELETE FROM asignatura_responsable WHERE idAsignatura = '{$idAsignaturaEscaped}'";
    if (!$db->query($sqlDelete)) {
        throw new Exception("Error al limpiar responsables anteriores: " . $db->error);
    }
    
    // Insertar nuevos responsables si los hay
    if (!empty($idsProfesores)) {
        $values = [];
        foreach ($idsProfesores as $idProf) {
            $idProfVal = intval($idProf);
            $values[] = "('{$idAsignaturaEscaped}', {$idProfVal})";
        }
        $sqlInsert = "INSERT INTO asignatura_responsable (idAsignatura, idProfesor) VALUES " . implode(', ', $values);
        if (!$db->query($sqlInsert)) {
            throw new Exception("Error al insertar nuevos responsables: " . $db->error);
        }
    }
    
    $db->commit();
    $db->autocommit(TRUE);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollback();
        $db->autocommit(TRUE);
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
