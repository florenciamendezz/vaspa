<?php
include_once '../lib/ControlAcceso.Class.php';
include_once '../modeloSistema/Asignatura.Class.php';
include_once '../modeloSistema/ProgramaPDFDetalle.Class.php';

$idAsignatura = $_GET["id"];
$Asignatura = new Asignatura($idAsignatura);
$anioActual = date('Y');

// Obtener programa actual
$ProgramaPDF = ProgramaPDFDetalle::obtenerPorAsignaturaYAnio($idAsignatura, $anioActual);

if (!$ProgramaPDF) {
    // Si no hay programa para este año, redirigir o mostrar error (aunque el boton modificar no deberia aparecer)
    // Pero como el requerimiento dice "obtenerUltimoPrograma", quizas estemos editando uno futuro.
    // Usaremos la logica de obtenerUltimoPrograma de Asignatura para ser consistentes.
    $Programa = $Asignatura->obtenerUltimoPrograma();
    if ($Programa) {
        $ProgramaPDF = ProgramaPDFDetalle::obtenerPorAsignaturaYAnio($idAsignatura, $Programa->getAnio());
    }
}

if (!$ProgramaPDF) {
     echo "<script>alert('No se encontró el programa para modificar.'); window.history.back();</script>";
     exit;
}

?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../lib/open-iconic-master/font/css/open-iconic-bootstrap.css" />
        <script src="../lib/JQuery/jquery-3.3.1.js"></script>
        <script src="../lib/bootstrap-4.1.1-dist/js/bootstrap.bundle.js"></script>
        <link rel="stylesheet" href="../lib/bootstrap-4.1.1-dist/css/bootstrap.css" />
        <title><?php echo Constantes::NOMBRE_SISTEMA; ?> - Modificar Programa PDF</title>
    </head>
    <body>
        <?php include_once '../gui/navbar.php'; ?>

        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h3>Modificar Programa de <?= $Asignatura->getNombre(); ?> (PDF)</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Archivo Actual:</strong> <?= $ProgramaPDF->getRutaArchivo(); ?><br>
                        <strong>Año:</strong> <?= $ProgramaPDF->getAnio(); ?>
                    </div>
                    
                    <div class="mb-3">
                        <embed src="../archivos/programas/<?= $ProgramaPDF->getRutaArchivo(); ?>" type="application/pdf" width="100%" height="600px" />
                    </div>
                
                    <form action="../controlSistema/programa.modificar.procesar.pdf.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="idAsignatura" value="<?= $Asignatura->getId(); ?>">
                        <input type="hidden" name="anio" value="<?= $ProgramaPDF->getAnio(); ?>">
                        
                        <div class="form-group">
                            <label for="archivoPDF">Nuevo Archivo PDF</label>
                            <input type="file" class="form-control-file" id="archivoPDF" name="archivoPDF" accept=".pdf" required>
                            <small class="form-text text-muted">Seleccione el nuevo archivo PDF para reemplazar el actual.</small>
                        </div>

                        <button type="submit" class="btn btn-warning">Actualizar Programa</button>
                        <a href="asignaturasDeProfesor.php" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
        <?php include_once '../gui/footer.php'; ?>
    </body>
</html>
