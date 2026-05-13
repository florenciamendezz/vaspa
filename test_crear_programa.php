<?php
include_once 'modeloSistema/ProgramaPDFDetalle.Class.php';
include_once 'modeloSistema/BDConexionSistema.Class.php';

echo "<h2>Test Crear Programa PDF Detalle</h2>";

$programa = new ProgramaPDFDetalle();
$idAsignatura = '1654'; // Valid ID
$anio = '2099'; // Future year for testing
$vigencia = '1';
$rutaArchivo = 'test_archivo.pdf';

// Clean up previous test if exists
$conn = BDConexionSistema::getInstancia();
$conn->query("DELETE FROM programa_pdf_detalle WHERE id_asignatura = '$idAsignatura' AND anio = '$anio'");
$conn->query("DELETE FROM programa WHERE idAsignatura = '$idAsignatura' AND anio = '$anio'");

echo "Intentando crear programa...<br>";
$resultado = $programa->crear($idAsignatura, $anio, $vigencia, $rutaArchivo);

if ($resultado) {
    echo "Resultado: EXITO<br>";
    
    // Verify inserts
    $res1 = $conn->query("SELECT * FROM programa WHERE idAsignatura = '$idAsignatura'");
    $res2 = $conn->query("SELECT * FROM programa_pdf_detalle WHERE id_asignatura = '$idAsignatura'");
    
    echo "Programa Legacy: " . ($res1->num_rows > 0 ? "Encontrado" : "No Encontrado") . "<br>";
    echo "Programa PDF: " . ($res2->num_rows > 0 ? "Encontrado" : "No Encontrado") . "<br>";
    
    // Cleanup
    $conn->query("DELETE FROM programa_pdf_detalle WHERE id_asignatura = '$idAsignatura'");
    $conn->query("DELETE FROM programa WHERE idAsignatura = '$idAsignatura'");
    
} else {
    echo "Resultado: FALLO<br>";
}
?>
