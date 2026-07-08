<?php
/**
 * Carga de materias de Ingeniería en Recursos Naturales Renovables (Carrera 023, Plan 023P1)
 * Departamentos: Ciencias Sociales (ID 2) / Ciencias Exactas y Naturales (ID 1)
 * NOTA: "Energías Renovables" omitida - sin código de asignatura
 */

$conn = new mysqli('localhost', 'root', '1234', 'bdgef_vaspa');
if ($conn->connect_error) die('Error: ' . $conn->connect_error);
$conn->set_charset('utf8');

$idPlan    = '023P1';
$idCarrera = '023';
$idEscuela = 6;    // Recursos Naturales
$idProfesor = 1;   // Placeholder

// codigo => [nombre, idDepartamento]  (1=CExactas, 2=CSociales)
$materias = [
    '1907' => ['Evaluación y Manejo de Pastizales',                          1],
    '1906' => ['Manejo de Recursos Bioacuáticos',                            1],
    '1905' => ['Manejo de Recursos Hídricos',                                1],
    '1904' => ['Tecnología de Gestión de las Organizaciones',                2],
    '1903' => ['Química Biológica',                                          1],
    '1527' => ['Química General',                                            1],
    '0901' => ['Análisis y Producción del Discurso (A)',                      2],
    '0433' => ['Frutihorticultura',                                          1],
    '0432' => ['Forestación y Viveros',                                      1],
    '0431' => ['Idioma Inglés',                                              2],
    '0425' => ['Dinámica Poblacional',                                       1],
    '0424' => ['Impacto Ambiental',                                          1],
    '0418' => ['Producción Bovina',                                          1],
    '0417' => ['Producción Ovina',                                           1],
    '0416' => ['Tecnología Pesquera',                                        1],
    '0415' => ['Formulación de Proyectos',                                   2],
    '0413' => ['Forrajes',                                                   1],
    '0410' => ['Manejo de Bosques',                                          1],
    '0409' => ['Manejo de Fauna',                                            1],
    '0408' => ['Relación Suelo-Planta-Animal',                               1],
    '0407' => ['Cartografía y Teledetección',                                1],
    '0406' => ['Acuicultura',                                                1],
    '0404' => ['Edafología',                                                 1],
    '0403' => ['Nutrición Animal',                                           1],
    '0402' => ['Ecología',                                                   1],
    '0399' => ['Física Aplicada',                                            1],
    '0398' => ['Genética',                                                   1],
    '0397' => ['Zoología',                                                   1],
    '0396' => ['Botánica',                                                   1],
    '0395' => ['Economía General',                                           2],
    '0394' => ['Fundamentos de Limnología y Oceanografía',                   1],
    '0393' => ['Estadística y Diseño Experimental',                          1],
    '0392' => ['Administración Estratégica',                                 2],
    '0391' => ['Principios de Geología',                                     1],
    '0390' => ['Matemática II',                                              1],
    '0388' => ['Sistemas Naturales',                                         1],
    '0387' => ['Matemática I',                                               1],
    '0324' => ['Biología General',                                           1],
    '0012' => ['Introducción al Conocimiento Científico',                    2],
    // OMITIDA: "Energías Renovables" - sin código de asignatura
];

$insertadas = 0;
$actualizadas = 0;
$sinCambio = 0;
$vinculadasPlan = 0;
$yaEnPlan = 0;
$vinculadasCarrera = 0;
$yaEnCarrera = 0;
$errores = [];

echo "=== CARGA DE MATERIAS - Ingeniería en Recursos Naturales Renovables (023 / Plan 023P1) ===\n";
echo "⚠️  OMITIDA: 'Energías Renovables' — no tiene código de asignatura\n\n";

foreach ($materias as $codigo => [$nombre, $idDepto]) {
    $codigoEsc = $conn->real_escape_string($codigo);
    $nombreEsc = $conn->real_escape_string($nombre);
    $deptoTag  = $idDepto == 1 ? '⚗️ CExactas' : 'CSociales';

    // --- 1. INSERTAR O ACTUALIZAR asignatura ---
    $res = $conn->query("SELECT id, idDepartamento FROM asignatura WHERE id = '$codigoEsc'");
    if ($res->num_rows > 0) {
        $row = $res->fetch_object();
        if ($row->idDepartamento != $idDepto) {
            $conn->query("UPDATE asignatura SET idDepartamento = $idDepto WHERE id = '$codigoEsc'");
            echo "[ACTUALIZADO DEPTO] $codigo - $nombre ($deptoTag)\n";
            $actualizadas++;
        } else {
            $sinCambio++;
        }
    } else {
        $sql = "INSERT INTO asignatura (id, nombre, idDepartamento, idEscuela, idProfesor, es_institucional)
                VALUES ('$codigoEsc', '$nombreEsc', $idDepto, $idEscuela, $idProfesor, 0)";
        if ($conn->query($sql)) {
            echo "[INSERTADA]   $codigo - $nombre [$deptoTag]\n";
            $insertadas++;
        } else {
            echo "[ERROR] $codigo - $nombre: " . $conn->error . "\n";
            $errores[] = "$codigo: " . $conn->error;
        }
    }

    // --- 2. VINCULAR plan_asignatura ---
    $r2 = $conn->query("SELECT 1 FROM plan_asignatura WHERE idPlan = '$idPlan' AND idAsignatura = '$codigoEsc'");
    if ($r2->num_rows == 0) {
        if (!$conn->query("INSERT INTO plan_asignatura (idPlan, idAsignatura, tieneCorrelativa) VALUES ('$idPlan', '$codigoEsc', 0)")) {
            $errores[] = "plan $codigo: " . $conn->error;
        } else {
            $vinculadasPlan++;
        }
    } else {
        $yaEnPlan++;
    }

    // --- 3. VINCULAR carrera_asignatura ---
    $r3 = $conn->query("SELECT 1 FROM carrera_asignatura WHERE idCarrera = '$idCarrera' AND idAsignatura = '$codigoEsc'");
    if ($r3->num_rows == 0) {
        if (!$conn->query("INSERT INTO carrera_asignatura (idCarrera, idAsignatura) VALUES ('$idCarrera', '$codigoEsc')")) {
            $errores[] = "carrera $codigo: " . $conn->error;
        } else {
            $vinculadasCarrera++;
        }
    } else {
        $yaEnCarrera++;
    }
}

echo "\n=== RESUMEN ===\n";
echo "Materias insertadas:               $insertadas\n";
echo "Materias ya existían (sin cambio): $sinCambio\n";
echo "Departamento actualizado:          $actualizadas\n";
echo "Vinculadas al plan $idPlan:       $vinculadasPlan\n";
echo "Ya estaban en plan:                $yaEnPlan\n";
echo "Vinculadas a carrera $idCarrera:          $vinculadasCarrera\n";
echo "Ya estaban en carrera:             $yaEnCarrera\n";
echo "⚠️  Omitidas (sin código):          1 (Energías Renovables)\n";

if ($errores) {
    echo "\n=== ERRORES ===\n";
    foreach ($errores as $e) echo "  - $e\n";
} else {
    echo "\n✓ Sin errores. Carga completada.\n";
}
