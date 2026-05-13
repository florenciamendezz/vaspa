<?php
include_once 'modeloSistema/BDConexionSistema.Class.php';

$idAsignatura = 2138;

echo "<h2>Fixing Program Status for Subject $idAsignatura</h2>";

// Get the latest program for this subject
$sql = "SELECT id FROM programa WHERE idAsignatura = $idAsignatura ORDER BY id DESC LIMIT 1";
$res = BDConexionSistema::getInstancia()->query($sql);

if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $idPrograma = $row['id'];
    echo "Found Program ID: $idPrograma<br>";
    
    // Update to NULLs
    // Note: We need to be careful not to overwrite if they are actually 1 (approved).
    // But the user said it says "desaprobado", so they are likely 0.
    // We will set them to NULL if they are 0.
    
    $sqlUpdate = "UPDATE programa SET 
                  aprobadoVa = NULLIF(aprobadoVa, 0),
                  aprobadoDepto = NULLIF(aprobadoDepto, 0),
                  aprobadoEscuela = NULLIF(aprobadoEscuela, 0)
                  WHERE id = $idPrograma";
                  
    if (BDConexionSistema::getInstancia()->query($sqlUpdate)) {
        echo "Updated legacy program $idPrograma: 0s converted to NULLs.<br>";
    } else {
        echo "Error updating legacy program: " . BDConexionSistema::getInstancia()->error . "<br>";
    }
    
    // Also update PDF detail if it exists
    $sqlPdf = "SELECT id FROM programa_pdf_detalle WHERE id_asignatura = $idAsignatura ORDER BY id DESC LIMIT 1";
    $resPdf = BDConexionSistema::getInstancia()->query($sqlPdf);
    if ($resPdf && $resPdf->num_rows > 0) {
        $rowPdf = $resPdf->fetch_assoc();
        $idPdf = $rowPdf['id'];
        echo "Found PDF Program ID: $idPdf<br>";
        
        $sqlUpdatePdf = "UPDATE programa_pdf_detalle SET 
                      aprobado_va = NULLIF(aprobado_va, 0),
                      aprobado_depto = NULLIF(aprobado_depto, 0),
                      aprobado_escuela = NULLIF(aprobado_escuela, 0)
                      WHERE id = $idPdf";

        if (BDConexionSistema::getInstancia()->query($sqlUpdatePdf)) {
            echo "Updated PDF program $idPdf: 0s converted to NULLs.<br>";
        } else {
             echo "Error updating PDF program: " . BDConexionSistema::getInstancia()->error . "<br>";
        }
    }
    
} else {
    echo "No program found for subject $idAsignatura.<br>";
}
?>
