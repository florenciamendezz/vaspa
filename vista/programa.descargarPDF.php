<?php
session_start();
// Verificamos si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../app/index.php");
    exit;
}

require_once '../modeloSistema/BDConexionSistema.Class.php';
require_once '../modeloSistema/Programa.Class.php';

// Validamos que el id del programa este definido
if (!isset($_GET['id']) || $_GET['id'] == "" || !is_numeric($_GET['id']) || $_GET['id'] < 0){
    die('No se ha especificado correctamente el ID del programa.');
}

$idPrograma = $_GET['id'];
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'legacy'; // 'pdf' o 'legacy'

if ($tipo == 'pdf') {
    // Descarga directa de programa_pdf_detalle
    $sqlPDF = "SELECT ruta_archivo FROM programa_pdf_detalle WHERE id = {$idPrograma}";
    $resPDF = BDConexionSistema::getInstancia()->query($sqlPDF);

    if ($resPDF && $resPDF->num_rows > 0) {
        $pdfData = $resPDF->fetch_assoc();
        $ruta = '../archivos/programas/' . $pdfData['ruta_archivo'];

        if (file_exists($ruta)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="'.basename($ruta).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($ruta));
            readfile($ruta);
            exit;
        } else {
            die('El archivo PDF físico no se encuentra en el servidor.');
        }
    } else {
        die('No se encontró el registro del programa PDF.');
    }

} else {
    // Lógica Legacy (busca en programa y luego si tiene PDF asociado)
    // Obtenemos datos del programa base para saber asignatura y año
    $sql = "SELECT idAsignatura, anio FROM programa WHERE id = {$idPrograma}";
    $res = BDConexionSistema::getInstancia()->query($sql);

    if ($res && $res->num_rows > 0) {
        $prog = $res->fetch_assoc();
        $idAsignatura = $prog['idAsignatura'];
        $anio = $prog['anio'];

        // Buscamos si existe un PDF asociado en programa_pdf_detalle
        $sqlPDF = "SELECT ruta_archivo FROM programa_pdf_detalle 
                   WHERE id_asignatura = '{$idAsignatura}' AND anio = {$anio}";
        $resPDF = BDConexionSistema::getInstancia()->query($sqlPDF);

        if ($resPDF && $resPDF->num_rows > 0) {
            // Existe PDF, lo descargamos
            $pdfData = $resPDF->fetch_assoc();
            $ruta = '../archivos/programas/' . $pdfData['ruta_archivo'];

            if (file_exists($ruta)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="'.basename($ruta).'"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($ruta));
                readfile($ruta);
                exit;
            } else {
                die('El archivo PDF físico no se encuentra en el servidor.');
            }
        } else {
            // No existe PDF, intentamos generar el legacy
            // Redirigimos a programa.generarPDF.php
            header("Location: programa.generarPDF.php?id={$idPrograma}");
            exit;
        }

    } else {
        die('No existe el programa solicitado.');
    }
}
?>
