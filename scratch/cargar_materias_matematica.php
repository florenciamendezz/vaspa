<?php
/**
 * Carga de materias del Profesorado en Matemática (Carrera 049)
 * Departamentos: Ciencias Sociales (ID 2) / Ciencias Exactas y Naturales (ID 1)
 * OMITIDAS: "Modelización Matemática: un desafío" y "Optimización Numérica" — sin código
 */

$conn = new mysqli('localhost', 'root', '1234', 'bdgef_vaspa');
if ($conn->connect_error) die('Error: ' . $conn->connect_error);
$conn->set_charset('utf8');

$plan = $conn->query("SELECT id, anio_inicio, estado FROM plan WHERE idCarrera = '049' ORDER BY anio_inicio DESC")->fetch_object();
echo "Plan: " . ($plan ? "$plan->id (inicio: $plan->anio_inicio, estado: $plan->estado)" : "NO ENCONTRADO") . "\n\n";

$idPlan    = $plan->id;
$idCarrera = '049';
$idEscuela = 11;   // Ciencias Básicas y Exactas
$idProfesor = 1;   // Placeholder

// codigo => [nombre, idDepartamento]  (1=CExactas, 2=CSociales)
$materias = [
    '0902' => ['Lógica',                                                          1],
    '1107' => ['Introducción al Conocimiento Científico',                         2],
    '1612' => ['Elementos de Algebra',                                            1],
    '0904' => ['Geometría I',                                                     1],
    '1108' => ['Ciencia, Universidad y Sociedad',                                 2],
    '1122' => ['Problemática Educativa',                                          2],
    '1530' => ['Análisis Matemático I',                                           1],
    '0901' => ['Análisis y Producción del Discurso (A)',                           2],
    '0070' => ['Álgebra Lineal',                                                  1],
    '1531' => ['Análisis Matemático II',                                          1],
    '0139' => ['Informática',                                                     1],
    '1599' => ['Probabilidades',                                                  1],
    '1643' => ['Geometría II',                                                    1],
    '0903' => ['Aprendizaje (A)',                                                  2],
    '0929' => ['Enseñanza y Curriculum',                                          2],
    '1532' => ['Física I',                                                        1],
    '1613' => ['Análisis Matemático III',                                         1],
    '0967' => ['Análisis Político y Organizacional del Sistema Educativo',        2],
    '1614' => ['Estructuras Algebraicas',                                         1],
    '1617' => ['Didáctica de la Matemática',                                      1],
    '1644' => ['Geometría III',                                                   1],
    '0933' => ['Teoría de Grafos y Programación Lineal',                          1],
    '1615' => ['Estadística',                                                     1],
    '1645' => ['Fundamentos e Historia de la Matemática',                         1],
    '1646' => ['Cálculo Numérico',                                                1],
    '1616' => ['Taller de Práctica Docente (A)',                                  1],
    '1208' => ['Inglés (Interpretación de Textos)',                               2],
    // OMITIDAS: "Modelización Matemática: un desafío" y "Optimización Numérica" — sin código
];

$insertadas = 0; $actualizadas = 0; $sinCambio = 0;
$vinculadasPlan = 0; $yaEnPlan = 0;
$vinculadasCarrera = 0; $yaEnCarrera = 0;
$errores = [];

echo "=== CARGA DE MATERIAS - Profesorado en Matemática (049 / Plan $idPlan) ===\n";
echo "⚠️  OMITIDAS: 'Modelización Matemática: un desafío' y 'Optimización Numérica' — sin código\n\n";

foreach ($materias as $codigo => [$nombre, $idDepto]) {
    $codigoEsc = $conn->real_escape_string($codigo);
    $nombreEsc = $conn->real_escape_string($nombre);
    $deptoTag  = $idDepto == 1 ? '⚗️ CExactas' : 'CSociales';

    // 1. Insertar o actualizar
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
echo "Vinculadas al plan $idPlan:        $vinculadasPlan\n";
echo "Ya estaban en plan:                $yaEnPlan\n";
echo "Vinculadas a carrera $idCarrera:           $vinculadasCarrera\n";
echo "Ya estaban en carrera:             $yaEnCarrera\n";
echo "⚠️  Omitidas (sin código):          2\n";
echo ($errores ? "\n=== ERRORES ===\n" . implode("\n", array_map(fn($e)=>"  - $e", $errores)) . "\n" : "\n✓ Sin errores. Carga completada.\n");
