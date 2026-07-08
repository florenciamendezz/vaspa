<?php
/**
 * Carga de materias del Analista de Sistemas (Carrera 016, Plan 016P5 - vigente 2013)
 * Departamentos: Ciencias Sociales (ID 2) / Ciencias Exactas y Naturales (ID 1)
 */

$conn = new mysqli('localhost', 'root', '1234', 'bdgef_vaspa');
if ($conn->connect_error) die('Error: ' . $conn->connect_error);
$conn->set_charset('utf8');

$idPlan    = '016P5';
$idCarrera = '016';
$idEscuela = 8;    // Sistemas e Informática
$idProfesor = 1;   // Placeholder

// codigo => [nombre, idDepartamento]  (1=CExactas, 2=CSociales)
$materias = [
    '1107' => ['Introducción al Conocimiento Científico',      2],
    '1528' => ['Álgebra',                                      1],
    '1684' => ['Proceso de Desarrollo de Software',            1],
    '1530' => ['Análisis Matemático I',                        1],
    '1650' => ['Matemática Discreta',                          1],
    '1987' => ['Organización de las Computadoras',             1],
    '0901' => ['Análisis y Producción del Discurso (A)',        2],
    '1649' => ['Resolución de Problemas y Algoritmos (A)',      1],
    '1652' => ['Programación Orientada a Objetos',             1],
    '2137' => ['Arquitectura de las Computadoras',             1],
    '1654' => ['Requerimientos de Software',                   1],
    '1989' => ['Aspectos Profesionales',                       1],
    '1108' => ['Ciencia, Universidad y Sociedad',              2],
    '1656' => ['Estructura de Datos',                          1],
    '1657' => ['Sistemas Operativos',                          1],
    '1658' => ['Análisis y Diseño de Software',                1],
    '1659' => ['Base de datos',                                1],
    '1660' => ['Laboratorio de Programación',                  1],
    '1661' => ['Redes y Telecomunicaciones',                   1],
    '1662' => ['Fundamentos de Ciencias de la Computación',    1],
    '1663' => ['Validación y Verificación de Software',        1],
    '1664' => ['Gestión de Organizaciones',                    2],
    '1665' => ['Estadística I',                                1],
    '1666' => ['Sistemas Operativos Distribuidos',             1],
    '1667' => ['Laboratorio de Desarrollo de Software',        1],
    '1668' => ['Gestión de Proyectos de Software',             1],
    '0453' => ['Idioma moderno (Inglés)',                      2],
];

$insertadas = 0;
$actualizadas = 0;
$sinCambio = 0;
$vinculadasPlan = 0;
$yaEnPlan = 0;
$vinculadasCarrera = 0;
$yaEnCarrera = 0;
$errores = [];

echo "=== CARGA DE MATERIAS - Analista de Sistemas (016 / Plan 016P5) ===\n\n";

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
echo "Vinculadas al plan $idPlan:        $vinculadasPlan\n";
echo "Ya estaban en plan:                $yaEnPlan\n";
echo "Vinculadas a carrera $idCarrera:           $vinculadasCarrera\n";
echo "Ya estaban en carrera:             $yaEnCarrera\n";

if ($errores) {
    echo "\n=== ERRORES ===\n";
    foreach ($errores as $e) echo "  - $e\n";
} else {
    echo "\n✓ Sin errores. Carga completada.\n";
}
