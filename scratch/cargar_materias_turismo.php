<?php
/**
 * Carga de materias de Licenciatura en Turismo (Carrera 061)
 * Departamentos: Ciencias Sociales (ID 2) / Ciencias Exactas y Naturales (ID 1)
 */

$conn = new mysqli('localhost', 'root', '1234', 'bdgef_vaspa');
if ($conn->connect_error) die('Error: ' . $conn->connect_error);
$conn->set_charset('utf8');

$plan = $conn->query("SELECT id, anio_inicio, estado FROM plan WHERE idCarrera = '061' ORDER BY anio_inicio DESC")->fetch_object();
echo "Plan: " . ($plan ? "$plan->id (inicio: $plan->anio_inicio, estado: $plan->estado)" : "NO ENCONTRADO") . "\n\n";

$idPlan    = $plan->id;
$idCarrera = '061';
$idEscuela = 12;   // Turismo
$idProfesor = 1;

// codigo => [nombre, idDepartamento]  (1=CExactas, 2=CSociales)
$materias = [
    '1513' => ['Introducción al Turismo',                                                       2],
    '1517' => ['Geografía',                                                                     1],
    '0901' => ['Análisis y Producción del Discurso (A)',                                         2],
    '1514' => ['Procesos Históricos (A)',                                                        2],
    '1515' => ['Patrimonio Turístico: Circuitos I (A)',                                          2],
    '1516' => ['Inglés I (A)',                                                                  2],
    '1107' => ['Introducción al Conocimiento Científico',                                       2],
    '1518' => ['Introducción al Derecho',                                                       2],
    '1519' => ['Antropología',                                                                  2],
    '1520' => ['Introducción a la Economía',                                                    2],
    '1108' => ['Ciencia, Universidad y Sociedad',                                               2],
    '1522' => ['Metodología de la Investigación',                                               2],
    '1523' => ['Introducción a la Estadística',                                                 1],
    '1524' => ['Servicios Turísticos',                                                          2],
    '1521' => ['Inglés II (A)',                                                                 2],
    '1436' => ['Interpretación Ambiental y del Patrimonio: Circuitos II',                       2],
    '1437' => ['Aspectos Políticos y Socioeconómicos del Turismo',                              2],
    '1438' => ['Sociología',                                                                    2],
    '1525' => ['Parques Nacionales, Áreas Protegidas y Uso Público',                            2],
    '1526' => ['Ética y Deontología Profesional',                                               2],
    '1440' => ['Legislación Turística, Patrimonial y Ambiental',                               2],
    '1441' => ['Geografía Turística: Evaluación del Impacto Ambiental',                        2],
    '1442' => ['Mercadotecnia, Marketing y Promoción Turística',                               2],
    '1443' => ['Práctica Profesional I',                                                        2],
    '1439' => ['Inglés III (A)',                                                                2],
    '1444' => ['Planificación y Desarrollo Turístico',                                          2],
    '1450' => ['Gestión y Administración de Empresas Turísticas',                              2],
    '1451' => ['Política de Turismo',                                                           2],
    '1452' => ['Práctica Profesional II',                                                       2],
    '1445' => ['Inglés IV (A)',                                                                 2],
    '1446' => ['Teoría Turística (A)',                                                          2],
    '1447' => ['Formulación y Evaluación de Proyectos Turísticos (A)',                         2],
    '1448' => ['Conservación de Sitios Culturales, Arqueológicos y Paleontológicos (A)',       2],
    '1449' => ['Conservación de Recursos Naturales (A)',                                        1],
    '1456' => ['Dirección General',                                                             2],
    '1458' => ['Gestión del Patrimonio Cultural',                                               2],
    '1462' => ['Práctica Profesional III (Orientación Conservación del Patrimonio)',            2],
    '1453' => ['Inglés V (A)',                                                                  2],
    '1454' => ['Tesis de Licenciatura (A)',                                                     2],
];

$insertadas = 0; $actualizadas = 0; $sinCambio = 0;
$vinculadasPlan = 0; $yaEnPlan = 0;
$vinculadasCarrera = 0; $yaEnCarrera = 0;
$errores = [];

echo "=== CARGA DE MATERIAS - Licenciatura en Turismo (061 / Plan $idPlan) ===\n\n";

foreach ($materias as $codigo => [$nombre, $idDepto]) {
    $codigoEsc = $conn->real_escape_string($codigo);
    $nombreEsc = $conn->real_escape_string($nombre);
    $deptoTag  = $idDepto == 1 ? '⚗️ CExactas' : 'CSociales';

    // 1. Insertar o verificar
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
            echo "[ERROR] $codigo: " . $conn->error . "\n";
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
echo "Departamento actualizado:          $actualizadas\n";
echo "Vinculadas al plan $idPlan:       $vinculadasPlan\n";
echo "Ya estaban en plan:                $yaEnPlan\n";
echo "Vinculadas a carrera $idCarrera:          $vinculadasCarrera\n";
echo "Ya estaban en carrera:             $yaEnCarrera\n";
echo ($errores ? "\n=== ERRORES ===\n" . implode("\n", array_map(fn($e) => "  - $e", $errores)) . "\n" : "\n✓ Sin errores. Carga completada.\n");
