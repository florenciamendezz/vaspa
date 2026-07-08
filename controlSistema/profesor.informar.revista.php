<?php
include_once '../lib/ControlAcceso.Class.php';
require_once '../modeloSistema/BDConexionSistema.Class.php';
require_once '../modeloSistema/Profesor.Class.php';
require_once '../lib/notificacionesMail/notificacionCircuitoVaspa.php';

header('Content-Type: application/json; charset=UTF-8');

// Validar inicio de sesión
ControlAcceso::verificaLogin();

$usuario = $_SESSION['usuario'];
$rol = (isset($usuario->roles) && is_array($usuario->roles) && count($usuario->roles) > 0) ? $usuario->roles[0]->nombre : '';

// Validar que el usuario sea Profesor
if ($rol != PermisosSistema::ROL_PROFESOR) {
    echo json_encode(['success' => false, 'error' => 'No autorizado. Debe ser un Profesor.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

$tipo = isset($_POST['tipoModificacion']) ? trim($_POST['tipoModificacion']) : '';
$comentarios = isset($_POST['comentarios']) ? trim($_POST['comentarios']) : '';

if ($tipo === 'eliminar') {
    $detalleAsignatura = isset($_POST['materiaEliminar']) ? trim($_POST['materiaEliminar']) : '';
} elseif ($tipo === 'agregar') {
    $detalleAsignatura = isset($_POST['materiaAgregar']) ? trim($_POST['materiaAgregar']) : '';
} else {
    echo json_encode(['success' => false, 'error' => 'Tipo de modificación inválido.']);
    exit();
}

if (empty($detalleAsignatura)) {
    echo json_encode(['success' => false, 'error' => 'Debe seleccionar una asignatura.']);
    exit();
}

// Obtener datos del profesor para el email
$db = BDConexionSistema::getInstancia();
$email = $db->real_escape_string($usuario->email);
$sqlProf = "SELECT * FROM profesor WHERE email = '{$email}'";
$resProf = $db->query($sqlProf);

if ($resProf && $resProf->num_rows > 0) {
    $profesorObj = $resProf->fetch_object("Profesor");
    $profesorNombre = $profesorObj->getApellido() . ", " . $profesorObj->getNombre();
    $profesorEmail = $profesorObj->getEmail();
    
    // Llamar al servicio de notificación
    $enviado = notificacionCircuitoVaspa::notificarModificacionRevista($profesorNombre, $profesorEmail, $tipo, $detalleAsignatura, $comentarios);
    
    if ($enviado) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo enviar el correo de notificación.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No se encontraron los datos del profesor en el sistema.']);
}
?>
