<?php
/**
 * Carga de materias de Licenciatura en Letras (Carrera 060)
 * Todas: Ciencias Sociales (ID 2) / Escuela Letras (ID 10)
 */

$conn = new mysqli('localhost', 'root', '1234', 'bdgef_vaspa');
if ($conn->connect_error) die('Error: ' . $conn->connect_error);
$conn->set_charset('utf8');

$plan = $conn->query("SELECT id, anio_inicio, estado FROM plan WHERE idCarrera = '060' ORDER BY anio_inicio DESC")->fetch_object();
echo "Plan: " . ($plan ? "$plan->id (inicio: $plan->anio_inicio, estado: $plan->estado)" : "NO ENCONTRADO") . "\n\n";

$idPlan    = $plan->id;
$idCarrera = '060';
$idEscuela = 10;   // Letras
$idProfesor = 1;
$idDepto   = 2;    // Todas Ciencias Sociales

$materias = [
    '1018' => 'Teoría y Análisis Literario',
    '1019' => 'Literatura de Masas',
    '1107' => 'Introducción al Conocimiento Científico',
    '1234' => 'Gramática Española',
    '1020' => 'Lingüística I',
    '1021' => 'Literatura Griega I',
    '1108' => 'Ciencia, Universidad y Sociedad',
    '1550' => 'Introducción a la Filosofía',
    '0901' => 'Análisis y Producción del Discurso (A)',
    '0242' => 'Estética',
    '1022' => 'Literatura Griega II',
    '1023' => 'Lengua y Cultura Latinas I',
    '1564' => 'Literatura Española I',
    '1025' => 'Literatura Francesa',
    '1026' => 'Lengua y Culturas Latinas II',
    '1027' => 'Taller de Escritura',
    '1610' => 'Literatura Española II',
    '1030' => 'Literatura Latinoamericana I',
    '1031' => 'Lingüística II',
    '1510' => 'Seminario de Licenciatura I',
    '1611' => 'Optativa',
    '1032' => 'Literatura Argentina I',
    '1033' => 'Seminario de Teoría Literaria',
    '1034' => 'Literatura Latinoamericana II',
    '1511' => 'Seminario de Licenciatura II',
    '1035' => 'Literatura Inglesa y Norteamericana',
    '1036' => 'Seminario de Lingüística',
    '1037' => 'Literatura Argentina II',
    '1038' => 'Seminario de Literatura',
    '1039' => 'Historia de la Lengua',
    '1512' => 'Seminario de Tesis',
    '1196' => 'Idioma moderno Inglés',
    '1199' => 'Idioma moderno Francés',
];

$insertadas = 0; $sinCambio = 0;
$vinculadasPlan = 0; $yaEnPlan = 0;
$vinculadasCarrera = 0; $yaEnCarrera = 0;
$errores = [];

echo "=== CARGA DE MATERIAS - Licenciatura en Letras (060 / Plan $idPlan) ===\n\n";

foreach ($materias as $codigo => $nombre) {
    $codigoEsc = $conn->real_escape_string($codigo);
    $nombreEsc = $conn->real_escape_string($nombre);

    // 1. Insertar si no existe
    $res = $conn->query("SELECT id FROM asignatura WHERE id = '$codigoEsc'");
    if ($res->num_rows > 0) {
        $sinCambio++;
    } else {
        $sql = "INSERT INTO asignatura (id, nombre, idDepartamento, idEscuela, idProfesor, es_institucional)
                VALUES ('$codigoEsc', '$nombreEsc', $idDepto, $idEscuela, $idProfesor, 0)";
        if ($conn->query($sql)) {
            echo "[INSERTADA]   $codigo - $nombre\n";
            $insertadas++;
        } else {
            $errores[] = "$codigo: " . $conn->error;
        }
    }

    // 2. Vincular plan
    if ($conn->query("SELECT 1 FROM plan_asignatura WHERE idPlan='$idPlan' AND idAsignatura='$codigoEsc'")->num_rows == 0) {
        $conn->query("INSERT INTO plan_asignatura (idPlan, idAsignatura, tieneCorrelativa) VALUES ('$idPlan', '$codigoEsc', 0)") ? $vinculadasPlan++ : ($errores[] = "plan $codigo: " . $conn->error);
    } else { $yaEnPlan++; }

    // 3. Vincular carrera
    if ($conn->query("SELECT 1 FROM carrera_asignatura WHERE idCarrera='$idCarrera' AND idAsignatura='$codigoEsc'")->num_rows == 0) {
        $conn->query("INSERT INTO carrera_asignatura (idCarrera, idAsignatura) VALUES ('$idCarrera', '$codigoEsc')") ? $vinculadasCarrera++ : ($errores[] = "carrera $codigo: " . $conn->error);
    } else { $yaEnCarrera++; }
}

echo "\n=== RESUMEN ===\n";
echo "Materias insertadas:               $insertadas\n";
echo "Materias ya existían (compartidas): $sinCambio\n";
echo "Vinculadas al plan $idPlan:        $vinculadasPlan\n";
echo "Ya estaban en plan:                $yaEnPlan\n";
echo "Vinculadas a carrera $idCarrera:           $vinculadasCarrera\n";
echo "Ya estaban en carrera:             $yaEnCarrera\n";
echo ($errores ? "\n=== ERRORES ===\n" . implode("\n", array_map(fn($e)=>"  - $e", $errores)) . "\n" : "\n✓ Sin errores. Carga completada.\n");
