<?php
/**
 * Carga de materias de Licenciatura en Psicopedagogía (Carrera 045)
 * Todas pertenecen a: Ciencias Sociales (ID 2)
 */

$conn = new mysqli('localhost', 'root', '1234', 'bdgef_vaspa');
if ($conn->connect_error) die('Error: ' . $conn->connect_error);
$conn->set_charset('utf8');

// Verificar plan
$plan = $conn->query("SELECT id, anio_inicio, estado FROM plan WHERE idCarrera = '045' ORDER BY anio_inicio DESC")->fetch_object();
echo "Plan: " . ($plan ? "$plan->id (inicio: $plan->anio_inicio, estado: $plan->estado)" : "NO ENCONTRADO") . "\n\n";

$idPlan    = $plan->id;
$idCarrera = '045';
$idEscuela = 3;    // Psicopedagogía
$idProfesor = 1;   // Placeholder
$idDepto   = 2;    // Todas Ciencias Sociales

$materias = [
    '0906' => 'Fundamentos Bioneurologicos del Aprendizaje',
    '0907' => 'Seminario de Introducción a la Psicopedagogía',
    '1107' => 'Introducción al Conocimiento Científico',
    '1287' => 'Introducción a la Psicología',
    '0003' => 'Antropología Sociocultural',
    '1108' => 'Ciencia, Universidad y Sociedad',
    '1122' => 'Problemática Educativa',
    '1249' => 'Sociología General',
    '0901' => 'Análisis y Producción del Discurso (A)',
    '0318' => 'Teoría del Aprendizaje',
    '0909' => 'Teoría Psicoanalítica',
    '0910' => 'Neuropsiquiatría',
    '0911' => 'Problemática en Salud',
    '0912' => 'Seminario: Psicosocio-lingüística',
    '0967' => 'Análisis Político y Organizacional del Sistema Educativo',
    '1279' => 'Sociología de la Educación',
    '1264' => 'Psicología Evolutiva (A)',
    '0913' => 'Educación Formal I',
    '0914' => 'Teoría Psicosocial',
    '1561' => 'Psicopatología',
    '0917' => 'Educación Formal II',
    '0919' => 'Teoría y Metodología de la Investigación en Ciencias Sociales',
    '0923' => 'Seminario: Necesidades Educativas Especiales',
    '1562' => 'Seminario de Análisis de la Practica Profesional',
    '1559' => 'Clínica Psicopedagógica I (A)',
    '0925' => 'Seminario de Familia',
    '0927' => 'Seminario de Análisis Institucional',
    '0921' => 'Educación Formal III',
    '0926' => 'Orientación Educacional y Vocación Ocupacional (A)',
    '1560' => 'Clínica Psicopedagógica II (A)',
    '1563' => 'Seminario de Investigación Psicopedagógica (A)',
    '1265' => 'Residencia Profesional',
    '1290' => 'Práctica Profesional',
    '0421' => 'Idioma moderno (Francés o Inglés)',
];

$insertadas = 0; $sinCambio = 0;
$vinculadasPlan = 0; $yaEnPlan = 0;
$vinculadasCarrera = 0; $yaEnCarrera = 0;
$errores = [];

echo "=== CARGA DE MATERIAS - Licenciatura en Psicopedagogía (045 / Plan $idPlan) ===\n\n";

foreach ($materias as $codigo => $nombre) {
    $codigoEsc = $conn->real_escape_string($codigo);
    $nombreEsc = $conn->real_escape_string($nombre);

    // 1. Insertar o skip
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
            echo "[ERROR] $codigo - $nombre: " . $conn->error . "\n";
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
echo "Materias ya existían (sin cambio): $sinCambio\n";
echo "Vinculadas al plan $idPlan:      $vinculadasPlan\n";
echo "Ya estaban en plan:                $yaEnPlan\n";
echo "Vinculadas a carrera $idCarrera:          $vinculadasCarrera\n";
echo "Ya estaban en carrera:             $yaEnCarrera\n";
echo ($errores ? "\n=== ERRORES ===\n" . implode("\n", array_map(fn($e)=>"  - $e", $errores)) . "\n" : "\n✓ Sin errores. Carga completada.\n");
