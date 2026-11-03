<?php
require_once __DIR__ . '/../lib/ControlAcceso.Class.php';
ControlAcceso::verificaLogin();

// Validación de roles con permiso de generación o revisión
$UsuarioSes = $_SESSION['usuario'];
$rolesUsuario = array_map(function($r) { return $r->nombre; }, $UsuarioSes->roles ?? []);
$rolesValidos = [
    PermisosSistema::ROL_ADMIN,
    PermisosSistema::ROL_VINCULACION_ACADEMICA,
    PermisosSistema::ROL_DIRECTOR_DEPARTAMENTO,
    PermisosSistema::ROL_DIRECTOR_ESCUELA,
    PermisosSistema::ROL_PROFESOR
];
if (count(array_intersect($rolesValidos, $rolesUsuario)) === 0) {
    http_response_code(403);
    die('Acceso denegado: Su rol no cuenta con privilegios para generar programas PDF.');
}

include_once __DIR__ . '/../modeloSistema/BDConexionSistema.Class.php';
include_once __DIR__ . '/../modeloSistema/Programa.Class.php';
include_once __DIR__ . '/../modeloSistema/Asignatura.Class.php';
require_once __DIR__ . '/../modeloSistema/MYPDF.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id']) || intval($_GET['id']) <= 0) {
    mostrarPantallaError('Identificador de programa analítico inválido.');
}

$idPrograma = intval($_GET['id']);
$programa = new Programa($idPrograma);
if (is_null($programa->getId())) {
    mostrarPantallaError('No existe un programa de asignatura con el ID especificado.');
}

// Verificación de aprobación reglamentaria
if ($programa->getAprobadoVa() != '1' || $programa->getAprobadoDepto() != '1') {
    mostrarPantallaError('El programa de asignatura no se encuentra aprobado reglamentariamente para su generación.');
}

try {
    $pdf = new MYPDF($idPrograma);
    $pdf->generarPDFprograma();
} catch (Exception $exc) {
    mostrarPantallaError($exc->getMessage());
}

function mostrarPantallaError($mensajeError) {
    ?><html>
    <head>
        <meta charset="UTF-8">
        <script type="text/javascript" src="../lib/JQuery/jquery-3.3.1.js"></script>
        <script type="text/javascript" src="../lib/bootstrap-4.1.1-dist/js/bootstrap.min.js"></script>
        <link rel="stylesheet" href="../lib/bootstrap-4.1.1-dist/css/bootstrap.css" />
        <link rel="stylesheet" href="../lib/open-iconic-master/font/css/open-iconic-bootstrap.css" />       
        <title><?php echo Constantes::NOMBRE_SISTEMA; ?> - Error al Generar PDF</title> 
    </head>
    <body>
        <?php include_once "../gui/navbar.php"; ?>
        <div class="container mt-4">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Error en la Generación del Programa PDF</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8'); ?>
                    </div>   
                </div>
            </div>
        </div>
        <?php include_once "../gui/footer.php"; ?>   
    </body>
    </html><?php
    exit;
}
