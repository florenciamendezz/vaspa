<?php
/**
 * Carga de materias del Profesorado en Historia (Carrera 003, Plan 003P3)
 * Departamento: Ciencias Sociales (ID 2) / Ciencias Exactas y Naturales (ID 1)
 */

$conn = new mysqli('localhost', 'root', '1234', 'bdgef_vaspa');
if ($conn->connect_error) die('Error: ' . $conn->connect_error);
$conn->set_charset('utf8');

$idPlan    = '003P3';
$idCarrera = '003';
$idEscuela = 2;    // Escuela de Historia
$idProfesor = 1;   // Placeholder

// codigo => [nombre, idDepartamento]
$materias = [
    '0014' => ['Problemática de la Ciencia Histórica',                         2],
    '1080' => ['Geografía General',                                             2],
    '1107' => ['Introducción al Conocimiento Científico',                      2],
    '1064' => ['Teoría Política',                                               2],
    '1108' => ['Ciencia, Universidad y Sociedad',                              2],
    '1122' => ['Problemática Educativa',                                        2],
    '1249' => ['Sociología General',                                            2],
    '1250' => ['Economía General',                                              2],
    '0901' => ['Análisis y Producción del Discurso (A)',                        2],
    '0034' => ['Historia Antigua',                                              2],
    '1065' => ['Procesos Culturales Comparados en la Prehistoria',             1], // Ciencias Exactas
    '1066' => ['Arte y Medios',                                                 2],
    '1067' => ['Metodología y Técnicas de Investigación en Historia',           2],
    '1068' => ['Problemática de Derechos Humanos',                             2],
    '1233' => ['Historia Medieval',                                             2],
    '0903' => ['Aprendizaje (A)',                                               2],
    '0929' => ['Enseñanza y Curriculum',                                        2],
    '1069' => ['Historia Americana I',                                          2],
    '1070' => ['Seminario de Cultura y Sociedad',                              2],
    '1071' => ['Seminario de Economía y Sociedad',                             2],
    '1235' => ['Historia Moderna',                                              2],
    '0967' => ['Análisis Político y Organizacional del Sistema Educativo',      2],
    '1072' => ['Historia Americana II',                                         2],
    '1073' => ['Historia Argentina I',                                          2],
    '1074' => ['Historiografía Contemporánea',                                  2],
    '1236' => ['Historia Contemporánea',                                        2],
    '1075' => ['Historia Americana III',                                        2],
    '1076' => ['Historia Argentina II',                                         2],
    '1077' => ['Historia del Siglo XX',                                         2],
    '1078' => ['Didáctica Especial de la Historia',                            2],
    '1079' => ['Historiografía Americana y Argentina',                         2],
    '1237' => ['Taller de Práctica Docente',                                   2],
    '1245' => ['Seminario Optativo de Historia Regional',                      2],
    '1196' => ['Idioma moderno Inglés',                                         2],
    '1199' => ['Idioma moderno Francés',                                        2],
];

$insertadas = 0;
$actualizadas = 0;
$sinCambio = 0;
$vinculadasPlan = 0;
$yaEnPlan = 0;
$vinculadasCarrera = 0;
$yaEnCarrera = 0;
$errores = [];

echo "=== CARGA DE MATERIAS - Profesorado en Historia (003) ===\n\n";

foreach ($materias as $codigo => [$nombre, $idDepto]) {
    $codigoEsc = $conn->real_escape_string($codigo);
    $nombreEsc = $conn->real_escape_string($nombre);

    // --- 1. INSERTAR O ACTUALIZAR asignatura ---
    $res = $conn->query("SELECT id, nombre, idDepartamento FROM asignatura WHERE id = '$codigoEsc'");
    if ($res->num_rows > 0) {
        $row = $res->fetch_object();
        if ($row->idDepartamento != $idDepto) {
            $conn->query("UPDATE asignatura SET idDepartamento = $idDepto WHERE id = '$codigoEsc'");
            echo "[ACTUALIZADO DEPTO] $codigo - $row->nombre (Depto: $idDepto)\n";
            $actualizadas++;
        } else {
            $sinCambio++;
        }
    } else {
        $sql = "INSERT INTO asignatura (id, nombre, idDepartamento, idEscuela, idProfesor, es_institucional)
                VALUES ('$codigoEsc', '$nombreEsc', $idDepto, $idEscuela, $idProfesor, 0)";
        if ($conn->query($sql)) {
            $tag = $idDepto == 1 ? '[INSERTADA ⚗️ CExactas]' : '[INSERTADA]  ';
            echo "$tag $codigo - $nombre\n";
            $insertadas++;
        } else {
            echo "[ERROR] $codigo - $nombre: " . $conn->error . "\n";
            $errores[] = "$codigo: " . $conn->error;
        }
    }

    // --- 2. VINCULAR plan_asignatura ---
    $r2 = $conn->query("SELECT 1 FROM plan_asignatura WHERE idPlan = '$idPlan' AND idAsignatura = '$codigoEsc'");
    if ($r2->num_rows == 0) {
        if ($conn->query("INSERT INTO plan_asignatura (idPlan, idAsignatura, tieneCorrelativa) VALUES ('$idPlan', '$codigoEsc', 0)")) {
            $vinculadasPlan++;
        } else {
            $errores[] = "plan $codigo: " . $conn->error;
        }
    } else {
        $yaEnPlan++;
    }

    // --- 3. VINCULAR carrera_asignatura ---
    $r3 = $conn->query("SELECT 1 FROM carrera_asignatura WHERE idCarrera = '$idCarrera' AND idAsignatura = '$codigoEsc'");
    if ($r3->num_rows == 0) {
        if ($conn->query("INSERT INTO carrera_asignatura (idCarrera, idAsignatura) VALUES ('$idCarrera', '$codigoEsc')")) {
            $vinculadasCarrera++;
        } else {
            $errores[] = "carrera $codigo: " . $conn->error;
        }
    } else {
        $yaEnCarrera++;
    }
}

echo "\n=== RESUMEN ===\n";
echo "Materias insertadas:           $insertadas\n";
echo "Materias ya existían (sin cambio): $sinCambio\n";
echo "Departamento actualizado:      $actualizadas\n";
echo "Vinculadas al plan $idPlan:     $vinculadasPlan\n";
echo "Ya estaban en plan:            $yaEnPlan\n";
echo "Vinculadas a carrera $idCarrera:          $vinculadasCarrera\n";
echo "Ya estaban en carrera:         $yaEnCarrera\n";

if ($errores) {
    echo "\n=== ERRORES ===\n";
    foreach ($errores as $e) echo "  - $e\n";
} else {
    echo "\n✓ Sin errores. Carga completada.\n";
}
