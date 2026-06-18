<?php
/**
 * Carga de materias:
 *   072 - Licenciatura en Sistemas (Plan 072P2)
 *   912 - Tecnicatura Universitaria en Gestión de las Organizaciones
 */

$conn = new mysqli('localhost', 'root', '1234', 'bdgef_vaspa');
if ($conn->connect_error) die('Error: ' . $conn->connect_error);
$conn->set_charset('utf8');

// ─── Verificar planes ──────────────────────────────────────────────────────
foreach (['072', '912'] as $idC) {
    $p = $conn->query("SELECT id, anio_inicio, estado FROM plan WHERE idCarrera = '$idC' ORDER BY anio_inicio DESC")->fetch_object();
    echo "Carrera $idC → Plan: " . ($p ? "$p->id ($p->anio_inicio, $p->estado)" : "NO ENCONTRADO") . "\n";
}
echo "\n";

// ─── Helper ───────────────────────────────────────────────────────────────
function cargarMaterias($conn, $idPlan, $idCarrera, $idEscuela, $materias, &$totales) {
    $idProfesor = 1;
    foreach ($materias as $codigo => [$nombre, $idDepto]) {
        $ce = $conn->real_escape_string($codigo);
        $ne = $conn->real_escape_string($nombre);
        $tag = $idDepto == 1 ? '⚗️ CExactas' : 'CSociales';

        // 1. Asignatura
        $res = $conn->query("SELECT id, idDepartamento FROM asignatura WHERE id = '$ce'");
        if ($res->num_rows > 0) {
            $row = $res->fetch_object();
            if ($row->idDepartamento != $idDepto) {
                $conn->query("UPDATE asignatura SET idDepartamento = $idDepto WHERE id = '$ce'");
                echo "  [ACTUALIZADO DEPTO] $codigo - $nombre ($tag)\n";
                $totales['actualizadas']++;
            } else {
                $totales['sinCambio']++;
            }
        } else {
            $sql = "INSERT INTO asignatura (id, nombre, idDepartamento, idEscuela, idProfesor, es_institucional)
                    VALUES ('$ce', '$ne', $idDepto, $idEscuela, $idProfesor, 0)";
            if ($conn->query($sql)) {
                echo "  [INSERTADA]   $codigo - $nombre [$tag]\n";
                $totales['insertadas']++;
            } else {
                echo "  [ERROR] $codigo: " . $conn->error . "\n";
                $totales['errores'][] = "$codigo: " . $conn->error;
            }
        }

        // 2. Plan
        if ($conn->query("SELECT 1 FROM plan_asignatura WHERE idPlan='$idPlan' AND idAsignatura='$ce'")->num_rows == 0) {
            $conn->query("INSERT INTO plan_asignatura (idPlan, idAsignatura, tieneCorrelativa) VALUES ('$idPlan', '$ce', 0)") ? $totales['vinPlan']++ : ($totales['errores'][] = "plan $codigo: " . $conn->error);
        } else { $totales['yaEnPlan']++; }

        // 3. Carrera
        if ($conn->query("SELECT 1 FROM carrera_asignatura WHERE idCarrera='$idCarrera' AND idAsignatura='$ce'")->num_rows == 0) {
            $conn->query("INSERT INTO carrera_asignatura (idCarrera, idAsignatura) VALUES ('$idCarrera', '$ce')") ? $totales['vinCarrera']++ : ($totales['errores'][] = "carrera $codigo: " . $conn->error);
        } else { $totales['yaEnCarrera']++; }
    }
}

function imprimirResumen($label, $idPlan, $idCarrera, $t) {
    echo "\n=== RESUMEN: $label ===\n";
    echo "Insertadas:              {$t['insertadas']}\n";
    echo "Ya existían:             {$t['sinCambio']}\n";
    echo "Depto actualizado:       {$t['actualizadas']}\n";
    echo "Vinculadas plan $idPlan: {$t['vinPlan']}\n";
    echo "Ya en plan:              {$t['yaEnPlan']}\n";
    echo "Vinculadas carrera $idCarrera:    {$t['vinCarrera']}\n";
    echo "Ya en carrera:           {$t['yaEnCarrera']}\n";
    echo (empty($t['errores']) ? "✓ Sin errores.\n" : "ERRORES: " . implode(', ', $t['errores']) . "\n");
}

$base = ['insertadas'=>0,'actualizadas'=>0,'sinCambio'=>0,'vinPlan'=>0,'yaEnPlan'=>0,'vinCarrera'=>0,'yaEnCarrera'=>0,'errores'=>[]];

// ══════════════════════════════════════════════════════════════════════════
// 072 - LICENCIATURA EN SISTEMAS
// NOTA: 1667 (Laboratorio de Desarrollo de Software) fue eliminado como
//       duplicado → se usa 2138 (el correcto, ya vinculado a 072)
// ══════════════════════════════════════════════════════════════════════════
echo "=== CARGA: Licenciatura en Sistemas (072 / Plan 072P2) ===\n";
echo "  ⚠️  Código 1667 reemplazado por 2138 (Laboratorio de Desarrollo de Software)\n\n";

$t072 = $base;
$materias072 = [
    '1107' => ['Introducción al Conocimiento Científico',          2],
    '1528' => ['Álgebra',                                          1],
    '1684' => ['Proceso de Desarrollo de Software',                1],
    '1530' => ['Análisis Matemático I',                            1],
    '1650' => ['Matemática Discreta',                              1],
    '1987' => ['Organización de las Computadoras',                 1],
    '0901' => ['Análisis y Producción del Discurso (A)',            2],
    '1649' => ['Resolución de Problemas y Algoritmos (A)',          1],
    '1531' => ['Análisis Matemático II',                           1],
    '1652' => ['Programación Orientada a Objetos',                 1],
    '1654' => ['Requerimientos de Software',                       1],
    '1655' => ['Aspectos Profesionales',                           1],
    '2137' => ['Arquitectura de las Computadoras',                 1],
    '1108' => ['Ciencia, Universidad y Sociedad',                  2],
    '1656' => ['Estructura de Datos',                              1],
    '1657' => ['Sistemas Operativos',                              1],
    '1658' => ['Análisis y Diseño de Software',                    1],
    '1659' => ['Base de datos',                                    1],
    '1660' => ['Laboratorio de Programación',                      1],
    '1661' => ['Redes y Telecomunicaciones',                       1],
    '1662' => ['Fundamentos de Ciencias de la Computación',        1],
    '1663' => ['Validación y Verificación de Software',            1],
    '1664' => ['Gestión de Organizaciones',                        2],
    '1665' => ['Estadística I',                                    1],
    '1666' => ['Sistemas Operativos Distribuidos',                 1],
    '2138' => ['Laboratorio de Desarrollo de Software',            1], // ← 1667 reemplazado
    '1668' => ['Gestión de Proyectos de Software',                 1],
    '1670' => ['Programación Lógica y Funcional',                  1],
    '1671' => ['Laboratorio de Redes',                             1],
    '1672' => ['Base de Datos Distribuidas',                       1],
    '1673' => ['Gestión de Calidad',                               1],
    '1345' => ['Inteligencia Artificial',                          1],
    '1674' => ['Estadística II',                                   1],
    '1675' => ['Fundamentos de Lenguaje de Programación',          1],
    '1676' => ['Sistemas de Información',                          2],
    '1677' => ['Taller de Metodología de la Investigación',        1],
    '1678' => ['Intérpretes y Compiladores',                       1],
    '1679' => ['Seminario de Programación',                        1],
    '1680' => ['Seminario de Hardware y Redes de Datos',           1],
    '1681' => ['Seminario de Sistemas',                            1],
    '1683' => ['Práctica Profesional',                             1],
    '1682' => ['Tesina de Grado (A)',                              1],
    '0453' => ['Idioma moderno (Inglés)',                          2],
];
cargarMaterias($conn, '072P2', '072', 8, $materias072, $t072);
imprimirResumen('Licenciatura en Sistemas', '072P2', '072', $t072);

// ══════════════════════════════════════════════════════════════════════════
// 912 - TECNICATURA EN GESTIÓN DE LAS ORGANIZACIONES
// ══════════════════════════════════════════════════════════════════════════
$plan912 = $conn->query("SELECT id FROM plan WHERE idCarrera = '912' ORDER BY anio_inicio DESC")->fetch_object();
$idPlan912 = $plan912 ? $plan912->id : null;
echo "\n\n=== CARGA: Tecnicatura en Gestión de las Organizaciones (912 / Plan $idPlan912) ===\n\n";

$t912 = $base;
$materias912 = [
    '0387' => ['Matemática I',                                     1],
    '1107' => ['Introducción al Conocimiento Científico',          2],
    '1127' => ['Sistemas Contables I',                             2],
    '0390' => ['Matemática II',                                    1],
    '1108' => ['Ciencia, Universidad y Sociedad',                  2],
    '1133' => ['Administración I',                                 2],
    '0901' => ['Análisis y Producción del Discurso (A)',            2],
    '1134' => ['Derecho Empresarial I',                            2],
    '1135' => ['Comportamiento Organizacional',                    2],
    '1136' => ['Administración II',                                2],
    '1137' => ['Economía I',                                       2],
    '1138' => ['Cálculo Financiero',                               2],
    '1139' => ['Sistemas Contables II',                            2],
    '1147' => ['Optativa / Electiva',                              2],
    '1221' => ['Estadística',                                      2],
    '1141' => ['Comercialización I',                               2],
    '1142' => ['Derecho Empresarial II',                           2],
    '1143' => ['Técnica Impositiva',                               2],
    '1144' => ['Seminario de Gestión de las Organizaciones',       2],
    '1145' => ['Nivel de Ofimática',                               1],
];

// Escuela para Gestión: buscarla
$esc = $conn->query("SELECT id FROM escuela WHERE nombre LIKE '%Administraci%' OR nombre LIKE '%Econom%' OR nombre LIKE '%Gesti%' LIMIT 1")->fetch_object();
$idEsc912 = $esc ? $esc->id : 5; // fallback: Administración y Economía
echo "  Escuela asignada: $idEsc912\n\n";

if ($idPlan912) {
    cargarMaterias($conn, $idPlan912, '912', $idEsc912, $materias912, $t912);
    imprimirResumen('Tecnicatura Gestión Org.', $idPlan912, '912', $t912);
} else {
    echo "ERROR: No se encontró plan vigente para carrera 912\n";
}
