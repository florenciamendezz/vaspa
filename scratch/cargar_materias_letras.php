<?php
/**
 * Carga de materias del Profesorado en Letras (Carrera 001, Plan 001P2)
 * Departamento: Ciencias Sociales (ID 2)
 * 
 * Este script:
 * 1. Inserta las materias faltantes en tabla 'asignatura'
 * 2. Las vincula en 'plan_asignatura' (plan 001P2)
 * 3. Las vincula en 'carrera_asignatura' (carrera 001)
 * 4. Actualiza el departamento de las ya existentes si fuera necesario
 */

$conn = new mysqli('localhost', 'root', '1234', 'bdgef_vaspa');
if ($conn->connect_error) die('Error: ' . $conn->connect_error);
$conn->set_charset('utf8');

$idPlan    = '001P2';
$idCarrera = '001';
$idDepto   = 2;    // Ciencias Sociales
$idEscuela = 10;   // Escuela de Letras
$idProfesor = 1;   // Placeholder - profesor real se asignará después

// Lista completa: codigo => nombre
$materias = [
    '1018' => 'Teoría y Análisis Literario',
    '1019' => 'Literatura de Masas',
    '1107' => 'Introducción al Conocimiento Científico',
    '1234' => 'Gramática Española',
    '1020' => 'Lingüística I',
    '1021' => 'Literatura Griega I',
    '1108' => 'Ciencia, Universidad y Sociedad',
    '1122' => 'Problemática Educativa',
    '0901' => 'Análisis y Producción del Discurso (A)',
    '1022' => 'Literatura Griega II',
    '1023' => 'Lengua y Cultura Latinas I',
    '1564' => 'Literatura Española I',
    '1025' => 'Literatura Francesa',
    '1026' => 'Lengua y Culturas Latinas II',
    '1027' => 'Taller de Escritura',
    '1610' => 'Literatura Española II',
    '0903' => 'Aprendizaje (A)',
    '0929' => 'Enseñanza y Curriculum',
    '1030' => 'Literatura Latinoamericana I',
    '1031' => 'Lingüística II',
    '0967' => 'Análisis Político y Organizacional del Sistema Educativo',
    '1032' => 'Literatura Argentina I',
    '1033' => 'Seminario de Teoría Literaria',
    '1034' => 'Literatura Latinoamericana II',
    '1035' => 'Literatura Inglesa y Norteamericana',
    '1036' => 'Seminario de Lingüística',
    '1037' => 'Literatura Argentina II',
    '1267' => 'Didáctica Especial',
    '1038' => 'Seminario de Literatura',
    '1039' => 'Historia de la Lengua',
    '1284' => 'Taller de Práctica Docente',
    '0421' => 'Idioma moderno (Francés o Inglés)',
];

$insertadas = 0;
$actualizadas = 0;
$yaVinculadasPlan = 0;
$vinculadasPlan = 0;
$yaVinculadasCarrera = 0;
$vinculadasCarrera = 0;
$errores = [];

echo "=== INICIANDO CARGA DE MATERIAS - Profesorado en Letras ===\n\n";

foreach ($materias as $codigo => $nombre) {
    $codigoEsc = $conn->real_escape_string($codigo);
    $nombreEsc = $conn->real_escape_string($nombre);

    // --- 1. INSERTAR O ACTUALIZAR en tabla asignatura ---
    $resExiste = $conn->query("SELECT id, idDepartamento FROM asignatura WHERE id = '$codigoEsc'");
    if ($resExiste->num_rows > 0) {
        $row = $resExiste->fetch_object();
        if ($row->idDepartamento != $idDepto) {
            $conn->query("UPDATE asignatura SET idDepartamento = $idDepto WHERE id = '$codigoEsc'");
            echo "[ACTUALIZADO DEPTO] $codigo - $nombre\n";
            $actualizadas++;
        }
        // ya existe, no se toca el nombre
    } else {
        $sql = "INSERT INTO asignatura (id, nombre, idDepartamento, idEscuela, idProfesor, es_institucional)
                VALUES ('$codigoEsc', '$nombreEsc', $idDepto, $idEscuela, $idProfesor, 0)";
        if ($conn->query($sql)) {
            echo "[INSERTADA]   $codigo - $nombre\n";
            $insertadas++;
        } else {
            echo "[ERROR INSERT] $codigo - $nombre: " . $conn->error . "\n";
            $errores[] = "$codigo: " . $conn->error;
        }
    }

    // --- 2. VINCULAR en plan_asignatura ---
    $resPA = $conn->query("SELECT 1 FROM plan_asignatura WHERE idPlan = '$idPlan' AND idAsignatura = '$codigoEsc'");
    if ($resPA->num_rows == 0) {
        $sql2 = "INSERT INTO plan_asignatura (idPlan, idAsignatura, tieneCorrelativa) VALUES ('$idPlan', '$codigoEsc', 0)";
        if ($conn->query($sql2)) {
            $vinculadasPlan++;
        } else {
            echo "[ERROR PLAN] $codigo: " . $conn->error . "\n";
            $errores[] = "plan $codigo: " . $conn->error;
        }
    } else {
        $yaVinculadasPlan++;
    }

    // --- 3. VINCULAR en carrera_asignatura ---
    $resCA = $conn->query("SELECT 1 FROM carrera_asignatura WHERE idCarrera = '$idCarrera' AND idAsignatura = '$codigoEsc'");
    if ($resCA->num_rows == 0) {
        $sql3 = "INSERT INTO carrera_asignatura (idCarrera, idAsignatura) VALUES ('$idCarrera', '$codigoEsc')";
        if ($conn->query($sql3)) {
            $vinculadasCarrera++;
        } else {
            echo "[ERROR CARRERA] $codigo: " . $conn->error . "\n";
            $errores[] = "carrera $codigo: " . $conn->error;
        }
    } else {
        $yaVinculadasCarrera++;
    }
}

echo "\n=== RESUMEN ===\n";
echo "Materias insertadas:          $insertadas\n";
echo "Materias actualizadas (depto):$actualizadas\n";
echo "Vinculadas al plan $idPlan:    $vinculadasPlan\n";
echo "Ya estaban en plan:           $yaVinculadasPlan\n";
echo "Vinculadas a carrera $idCarrera:        $vinculadasCarrera\n";
echo "Ya estaban en carrera:        $yaVinculadasCarrera\n";

if (count($errores) > 0) {
    echo "\n=== ERRORES ===\n";
    foreach ($errores as $e) echo "  - $e\n";
} else {
    echo "\n✓ Sin errores. Carga completada.\n";
}
