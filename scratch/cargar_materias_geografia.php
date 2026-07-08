<?php
/**
 * Carga de materias del Profesorado en Geografía (Carrera 004, Plan 004P2)
 * Departamentos: Ciencias Sociales (ID 2) / Ciencias Exactas y Naturales (ID 1)
 */

$conn = new mysqli('localhost', 'root', '1234', 'bdgef_vaspa');
if ($conn->connect_error) die('Error: ' . $conn->connect_error);
$conn->set_charset('utf8');

$idPlan    = '004P2';
$idCarrera = '004';
$idEscuela = 13;   // Geografía, Ordenamiento Territorial y Geoinformación
$idProfesor = 1;   // Placeholder

// codigo => [nombre, idDepartamento]  (1=CExactas, 2=CSociales)
$materias = [
    '0008' => ['Herramientas de Informática',                                          1],
    '1107' => ['Introducción al Conocimiento Científico',                              2],
    '1493' => ['Matemática Aplicada',                                                  1],
    '1497' => ['Introducción a la Geografía',                                          2],
    '1108' => ['Ciencia, Universidad y Sociedad',                                      2],
    '1122' => ['Problemática Educativa',                                               2],
    '1250' => ['Economía General',                                                     2],
    '1262' => ['Climatología',                                                         1],
    '0901' => ['Análisis y Producción del Discurso (A)',                               2],
    '1480' => ['Cartografía',                                                          2],
    '1481' => ['Teoría de la Geografía',                                               2],
    '1498' => ['Geomorfología',                                                        2],
    '1261' => ['Hidrografía',                                                          2],
    '1494' => ['Historia Social General',                                              2],
    '1505' => ['Estadística para las Ciencias Sociales',                               1],
    '0903' => ['Aprendizaje (A)',                                                       2],
    '0929' => ['Enseñanza y Curriculum',                                               2],
    '1257' => ['Biogeografía',                                                         1],
    '1259' => ['Geografía de la Población',                                            2],
    '1482' => ['Sociología Rural y Urbana',                                            2],
    '0967' => ['Análisis Político y Organizacional del Sistema Educativo',             2],
    '1483' => ['Metodología de la Investigación en Geografía',                         2],
    '1484' => ['Seminario de Integración: Ambientes Naturales y Acción Antrópica',     2],
    '1495' => ['Territorios Geográficos Mundiales',                                    2],
    '1499' => ['Geografía Económica y Política',                                       2],
    '1485' => ['Teledetección',                                                        2],
    '1486' => ['Geografía Rural',                                                      2],
    '1487' => ['Geografía Urbana',                                                     2],
    '1500' => ['Didáctica Especial de la Geografía',                                   2],
    '1488' => ['Seminario de Integración: Geografía de la Patagonia',                  2],
    '1496' => ['Territorios Geográficos de América',                                   2],
    '1501' => ['Geografía Regional Argentina',                                         2],
    '1502' => ['Taller de Práctica Docente',                                           2],
    '1508' => ['Idioma moderno (Francés o Inglés)',                                    2],
];

$insertadas = 0;
$actualizadas = 0;
$sinCambio = 0;
$vinculadasPlan = 0;
$yaEnPlan = 0;
$vinculadasCarrera = 0;
$yaEnCarrera = 0;
$errores = [];

echo "=== CARGA DE MATERIAS - Profesorado en Geografía (004) ===\n\n";

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
echo "Vinculadas al plan $idPlan:         $vinculadasPlan\n";
echo "Ya estaban en plan:                $yaEnPlan\n";
echo "Vinculadas a carrera $idCarrera:            $vinculadasCarrera\n";
echo "Ya estaban en carrera:             $yaEnCarrera\n";

if ($errores) {
    echo "\n=== ERRORES ===\n";
    foreach ($errores as $e) echo "  - $e\n";
} else {
    echo "\n✓ Sin errores. Carga completada.\n";
}
