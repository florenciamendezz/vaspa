<?php
require_once __DIR__ . '/../lib/ControlAcceso.Class.php';
ControlAcceso::verificaLogin();

// Validación de Autorización por Roles autorizados a consultar documentos en circuito
$UsuarioSes = $_SESSION['usuario'];
$rolesUsuario = array_map(function($r) { return $r->nombre; }, $UsuarioSes->roles ?? []);
$rolesValidos = [
    PermisosSistema::ROL_ADMIN,
    PermisosSistema::ROL_VINCULACION_ACADEMICA,
    PermisosSistema::ROL_DIRECTOR_DEPARTAMENTO,
    PermisosSistema::ROL_DIRECTOR_ESCUELA,
    PermisosSistema::ROL_PROFESOR
];
$autorizado = count(array_intersect($rolesValidos, $rolesUsuario)) > 0;

if (!$autorizado) {
    http_response_code(403);
    die('Acceso denegado: Su rol no posee privilegios para descargar programas analíticos.');
}

require_once __DIR__ . '/../modeloSistema/BDConexionSistema.Class.php';
require_once __DIR__ . '/../modeloSistema/Programa.Class.php';

// Validación estricta y casteo seguro de ID
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || intval($_GET['id']) <= 0) {
    http_response_code(400);
    die('Parámetro de identificación de programa inválido.');
}

$idPrograma = intval($_GET['id']);
$tipo = isset($_GET['tipo']) && $_GET['tipo'] === 'pdf' ? 'pdf' : 'legacy';

$db = BDConexionSistema::getInstancia();
$baseDir = realpath(__DIR__ . '/../archivos/programas');

if ($tipo === 'pdf') {
    $stmt = $db->prepare("SELECT ruta_archivo FROM programa_pdf_detalle WHERE id = ?");
    if (!$stmt) {
        http_response_code(500);
        die('Error en el servicio de datos.');
    }
    $stmt->bind_param("i", $idPrograma);
    $stmt->execute();
    $resPDF = $stmt->get_result();
    $stmt->close();

    if ($resPDF && $resPDF->num_rows > 0) {
        $pdfData = $resPDF->fetch_assoc();
        $nombreLimpio = basename($pdfData['ruta_archivo']);
        $rutaCompleta = realpath(__DIR__ . '/../archivos/programas/' . $nombreLimpio);

        // Prevención estricta de Path Traversal: asegurar que el archivo resida en archivos/programas
        if ($rutaCompleta !== false && strpos($rutaCompleta, $baseDir) === 0 && file_exists($rutaCompleta)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $nombreLimpio . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($rutaCompleta));
            readfile($rutaCompleta);
            exit;
        } else {
            http_response_code(404);
            die('El archivo PDF físico no se encuentra disponible.');
        }
    } else {
        http_response_code(404);
        die('Registro de programa PDF no encontrado.');
    }
} else {
    // Modo legacy: búsqueda en programa y derivación
    $stmt = $db->prepare("SELECT idAsignatura, anio FROM programa WHERE id = ?");
    if (!$stmt) {
        http_response_code(500);
        die('Error en el servicio de datos.');
    }
    $stmt->bind_param("i", $idPrograma);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();

    if ($res && $res->num_rows > 0) {
        $prog = $res->fetch_assoc();
        $idAsignatura = $prog['idAsignatura'];
        $anio = intval($prog['anio']);

        $stmtPDF = $db->prepare("SELECT ruta_archivo FROM programa_pdf_detalle WHERE id_asignatura = ? AND anio = ?");
        $stmtPDF->bind_param("si", $idAsignatura, $anio);
        $stmtPDF->execute();
        $resPDF = $stmtPDF->get_result();
        $stmtPDF->close();

        if ($resPDF && $resPDF->num_rows > 0) {
            $pdfData = $resPDF->fetch_assoc();
            $nombreLimpio = basename($pdfData['ruta_archivo']);
            $rutaCompleta = realpath(__DIR__ . '/../archivos/programas/' . $nombreLimpio);

            if ($rutaCompleta !== false && strpos($rutaCompleta, $baseDir) === 0 && file_exists($rutaCompleta)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . $nombreLimpio . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($rutaCompleta));
                readfile($rutaCompleta);
                exit;
            } else {
                http_response_code(404);
                die('El archivo PDF físico no se encuentra disponible.');
            }
        } else {
            // Derivación autenticada a programa.generarPDF.php
            header("Location: programa.generarPDF.php?id={$idPrograma}");
            exit;
        }
    } else {
        http_response_code(404);
        die('No existe el programa solicitado.');
    }
}
