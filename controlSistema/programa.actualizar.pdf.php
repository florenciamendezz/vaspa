<?php
include_once __DIR__ . '/../lib/ControlAcceso.Class.php';
ControlAcceso::verificaLogin();
ControlAcceso::requiereCsrf();
include_once __DIR__ . '/../modeloSistema/ProgramaPDFDetalle.Class.php';
include_once __DIR__ . '/../modeloSistema/Asignatura.Class.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['idPrograma']) && isset($_FILES['archivoPdf'])) {
    
    $idPrograma = intval($_POST['idPrograma']);
    $archivo = $_FILES['archivoPdf'];
    
    $programa = new ProgramaPDFDetalle($idPrograma);
    if (!$programa->getId()) {
        echo "<script>alert('Programa no encontrado.'); window.history.back();</script>";
        exit;
    }

    $idAsignatura = preg_replace('/[^a-zA-Z0-9]/', '', $programa->getIdAsignatura());
    $anio = intval($programa->getAnio());
    
    $Usuario = $_SESSION['usuario'];
    $rolNombre = $Usuario->roles[0]->nombre ?? '';

    if (!in_array($rolNombre, ['Vinculación Académica', 'Administrador', 'Director de Departamento', 'Director de Escuela', 'Secretario de Escuela'])) {
        echo "<script>alert('No tenés permisos para realizar esta acción.'); window.history.back();</script>";
        exit;
    }

    // Validación Anti-IDOR (SEC-011): Jurisdicción sobre el programa
    $asignatura = new Asignatura($idAsignatura);
    if ($rolNombre === 'Director de Departamento') {
        $email = strtolower(trim($Usuario->email));
        $idDeptoUsuario = ($email === strtolower(defined('MAIL_DEPTO_CS') ? MAIL_DEPTO_CS : '')) ? 2 : 1;
        if (intval($asignatura->getIdDepartamento()) !== $idDeptoUsuario) {
            http_response_code(403);
            die("Acceso denegado: No tiene jurisdicción sobre asignaturas de este departamento.");
        }
    } elseif ($rolNombre === 'Director de Escuela' || $rolNombre === 'Secretario de Escuela') {
        if ($asignatura->getEsInstitucional() == 1) {
            http_response_code(403);
            die("Acceso denegado: Las asignaturas institucionales no corresponden al ámbito de Escuela.");
        }
    }

    // Si el rol es VA pero el programa ya está listo para firma final (aprobadoDepto == 1), no se debe subir PDF aquí.
    if (($rolNombre == 'Vinculación Académica' || $rolNombre == 'Administrador') && $programa->getAprobadoDepto() == 1) {
        echo "<script>alert('En la etapa de firma final no se requiere subir un nuevo PDF.'); window.history.back();</script>";
        exit;
    }

    $rolSlug = 'VA';
    if ($rolNombre == 'Director de Departamento') {
        $rolSlug = 'Depto';
    } elseif ($rolNombre == 'Director de Escuela' || $rolNombre == 'Secretario de Escuela') {
        $rolSlug = 'Escuela';
    }

    // Manejo y validación estricta del archivo
    if (!isset($archivo) || $archivo['error'] !== UPLOAD_ERR_OK) {
        echo "<script>alert('Error en la transferencia del archivo o archivo ausente.'); window.history.back();</script>";
        exit;
    }

    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        echo "<script>alert('Solo se permiten archivos PDF.'); window.history.back();</script>";
        exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeReal = $finfo->file($archivo['tmp_name']);
    if ($mimeReal !== 'application/pdf') {
        echo "<script>alert('El contenido real del archivo no corresponde a un documento PDF.'); window.history.back();</script>";
        exit;
    }

    if ($archivo['size'] > 10 * 1024 * 1024) {
        echo "<script>alert('El archivo excede el tamaño máximo permitido (10 MB).'); window.history.back();</script>";
        exit;
    }
    
    // Directorio de destino
    $directorioDestino = __DIR__ . '/../archivos/programas/';
    if (!file_exists($directorioDestino)) {
        mkdir($directorioDestino, 0755, true);
    }
    
    // Nombre del archivo sanitizado
    $nombreArchivo = "prg_" . basename($idAsignatura) . "_{$anio}_firmado-{$rolSlug}_" . time() . ".pdf";
    $rutaCompleta = $directorioDestino . $nombreArchivo;
    
    if (move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
        // Guardar en base de datos usando el método del modelo
        $res = $programa->revisorSubePdfFirmado($rolNombre, $nombreArchivo);
        
        if ($res) {
            $redirectUrl = '../vista/inicio.php';
            echo "<script>alert('Programa firmado cargado correctamente como borrador de revisión. Presioná \"Enviar al siguiente\" para avanzar en el circuito.'); window.location.href = '{$redirectUrl}';</script>";
        } else {
            echo "<script>alert('Error al registrar el archivo firmado en la base de datos.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Error al guardar el archivo en el servidor.'); window.history.back();</script>";
    }
} else {
    header("Location: ../vista/inicio.php");
}
