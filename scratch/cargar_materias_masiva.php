<?php
/**
 * Carga masiva de 6 carreras:
 *  062 - Tecnicatura Universitaria en Turismo
 *  064 - Licenciatura en Geografía
 *  069 - Ingeniería Química
 *  913 - Licenciatura en Administración
 *  914 - Profesorado en Gestión de las Organizaciones
 *  918 - Licenciatura en Comunicación Social
 */

$conn = new mysqli('localhost', 'root', '1234', 'bdgef_vaspa');
if ($conn->connect_error) die('Error: ' . $conn->connect_error);
$conn->set_charset('utf8');

// ── Helper ─────────────────────────────────────────────────────────────────
function getPlan($conn, $idCarrera) {
    $p = $conn->query("SELECT id FROM plan WHERE idCarrera='$idCarrera' AND estado='vigente' ORDER BY anio_inicio DESC")->fetch_object();
    return $p ? $p->id : null;
}

function cargar($conn, $label, $idPlan, $idCarrera, $idEscuela, $materias) {
    $ins = $act = $skip = $vp = $yp = $vc = $yc = 0;
    $errs = [];
    echo "\n══════════════════════════════════════════════\n";
    echo " $label ($idCarrera / $idPlan)\n";
    echo "══════════════════════════════════════════════\n";
    foreach ($materias as $cod => [$nom, $dep]) {
        $ce = $conn->real_escape_string($cod);
        $ne = $conn->real_escape_string($nom);
        // 1. asignatura
        $r = $conn->query("SELECT id, idDepartamento FROM asignatura WHERE id='$ce'");
        if ($r->num_rows > 0) {
            $row = $r->fetch_object();
            if ($row->idDepartamento != $dep) {
                $conn->query("UPDATE asignatura SET idDepartamento=$dep WHERE id='$ce'");
                echo "  [ACT DEPTO] $cod - $nom\n"; $act++;
            } else { $skip++; }
        } else {
            $sql = "INSERT INTO asignatura (id,nombre,idDepartamento,idEscuela,idProfesor,es_institucional) VALUES ('$ce','$ne',$dep,$idEscuela,1,0)";
            if ($conn->query($sql)) { echo "  [+] $cod - $nom\n"; $ins++; }
            else { echo "  [ERR] $cod: ".$conn->error."\n"; $errs[]="$cod:".$conn->error; }
        }
        // 2. plan
        if ($conn->query("SELECT 1 FROM plan_asignatura WHERE idPlan='$idPlan' AND idAsignatura='$ce'")->num_rows==0) {
            $conn->query("INSERT INTO plan_asignatura(idPlan,idAsignatura,tieneCorrelativa) VALUES('$idPlan','$ce',0)") ? $vp++ : ($errs[]="plan $cod");
        } else $yp++;
        // 3. carrera
        if ($conn->query("SELECT 1 FROM carrera_asignatura WHERE idCarrera='$idCarrera' AND idAsignatura='$ce'")->num_rows==0) {
            $conn->query("INSERT INTO carrera_asignatura(idCarrera,idAsignatura) VALUES('$idCarrera','$ce')") ? $vc++ : ($errs[]="carrera $cod");
        } else $yc++;
    }
    echo "  → Insertadas:$ins | Ya existían:$skip | Depto actualizado:$act\n";
    echo "  → Plan $idPlan: vinculadas:$vp ya:$yp | Carrera $idCarrera: vinculadas:$vc ya:$yc\n";
    echo "  → " . (empty($errs) ? "✓ Sin errores" : "ERRORES: ".implode(', ',$errs)) . "\n";
}

// ══════════════════════════════════════════════════════════════════════════
// 062 - TECNICATURA UNIVERSITARIA EN TURISMO
// ══════════════════════════════════════════════════════════════════════════
$p062 = getPlan($conn, '062');
cargar($conn, 'Tecnicatura en Turismo', $p062, '062', 12, [
    '1513' => ['Introducción al Turismo',                                     2],
    '1466' => ['Práctica Profesional I',                                      2],
    '1517' => ['Geografía',                                                   1],
    '0901' => ['Análisis y Producción del Discurso (A)',                        2],
    '1514' => ['Procesos Históricos (A)',                                       2],
    '1515' => ['Patrimonio Turístico: Circuitos I (A)',                         2],
    '1516' => ['Inglés I (A)',                                                 2],
    '1107' => ['Introducción al Conocimiento Científico',                      2],
    '1518' => ['Introducción al Derecho',                                      2],
    '1519' => ['Antropología',                                                 2],
    '1520' => ['Introducción a la Economía',                                   2],
    '1108' => ['Ciencia, Universidad y Sociedad',                              2],
    '1440' => ['Legislación Turística, Patrimonial y Ambiental',              2],
    '1469' => ['Práctica Profesional II',                                      2],
    '1524' => ['Servicios Turísticos',                                         2],
    '1521' => ['Inglés II (A)',                                                2],
    '1436' => ['Interpretación Ambiental y del Patrimonio: Circuitos II',      2],
    '1437' => ['Aspectos Políticos y Socioeconómicos del Turismo',             2],
    '1438' => ['Sociología',                                                   2],
    '1525' => ['Parques Nacionales, Áreas Protegidas y Uso Público',           2],
    '1526' => ['Ética y Deontología Profesional',                              2],
    '1441' => ['Geografía Turística: Evaluación del Impacto Ambiental',       2],
    '1442' => ['Mercadotecnia, Marketing y Promoción Turística',              2],
    '1470' => ['Práctica Profesional III',                                     2],
    '1439' => ['Inglés III (A)',                                               2],
]);

// ══════════════════════════════════════════════════════════════════════════
// 064 - LICENCIATURA EN GEOGRAFÍA
// ══════════════════════════════════════════════════════════════════════════
$p064 = getPlan($conn, '064');
cargar($conn, 'Licenciatura en Geografía', $p064, '064', 13, [
    '0008' => ['Herramientas de Informática',                                  1],
    '1107' => ['Introducción al Conocimiento Científico',                      2],
    '1493' => ['Matemática Aplicada',                                          1],
    '1497' => ['Introducción a la Geografía',                                  2],
    '1108' => ['Ciencia, Universidad y Sociedad',                              2],
    '1250' => ['Economía General',                                             2],
    '1262' => ['Climatología',                                                 1],
    '0901' => ['Análisis y Producción del Discurso (A)',                        2],
    '1480' => ['Cartografía',                                                  2],
    '1481' => ['Teoría de la Geografía',                                       2],
    '1498' => ['Geomorfología',                                                2],
    '1261' => ['Hidrografía',                                                  2],
    '1494' => ['Historia Social General',                                      2],
    '1505' => ['Estadística para las Ciencias Sociales',                       1],
    '1257' => ['Biogeografía',                                                 1],
    '1259' => ['Geografía de la Población',                                    2],
    '1482' => ['Sociología Rural y Urbana',                                    2],
    '1483' => ['Metodología de la Investigación en Geografía',                 2],
    '1484' => ['Seminario de Integración: Ambientes Naturales y Acción Antrópica', 2],
    '1495' => ['Territorios Geográficos Mundiales',                            2],
    '1499' => ['Geografía Económica y Política',                               2],
    '1485' => ['Teledetección',                                                2],
    '1486' => ['Geografía Rural',                                              2],
    '1487' => ['Geografía Urbana',                                             2],
    '1488' => ['Seminario de Integración: Geografía de la Patagonia',          2],
    '1496' => ['Territorios Geográficos de América',                           2],
    '1501' => ['Geografía Regional Argentina',                                 2],
    '1508' => ['Idioma moderno (Francés o Inglés)',                            2],
    '2195' => ['Tesis',                                                        2],
]);

// ══════════════════════════════════════════════════════════════════════════
// 069 - INGENIERÍA QUÍMICA
// NOTA: Códigos duplicados en el listado (plan nuevo vs plan anterior)
//       se toma un único registro por código.
// ══════════════════════════════════════════════════════════════════════════
$p069 = getPlan($conn, '069');
cargar($conn, 'Ingeniería Química', $p069, '069', 11, [
    '1107' => ['Introducción al Conocimiento Científico',                  2],
    '1527' => ['Química General',                                          1],
    '1528' => ['Álgebra',                                                  1],
    '1108' => ['Ciencia, Universidad y Sociedad',                          2],
    '1529' => ['Química Inorgánica',                                       1],
    '1530' => ['Análisis Matemático I',                                    1],
    '1624' => ['Sistemas de Representación y Fundamentos de Informática',  1],
    '0901' => ['Análisis y Producción del Discurso (A)',                    2],
    '1531' => ['Análisis Matemático II',                                   1],
    '1532' => ['Física I',                                                 1],
    '1535' => ['Química Analítica I',                                      1],
    '1533' => ['Física II',                                                1],
    '1534' => ['Termodinámica',                                            1],
    '1536' => ['Química Orgánica I',                                       1],
    '1618' => ['Química Orgánica II',                                      1],
    '1613' => ['Análisis Matemático III',                                  1],
    '1619' => ['Fisicoquímica',                                            1],
    '1620' => ['Estadística Aplicada',                                     1],
    '1633' => ['Seguridad, Higiene y Gestión Ambiental',                   2],
    '1621' => ['Fenómeno del Transporte',                                  1],
    '1622' => ['Cálculo Numérico',                                         1],
    '2620' => ['Tecnología de Materiales',                                 1],
    '2556' => ['Química de los Procesos Biológicos',                       1],
    '2621' => ['Química Analítica (Avanzada)',                             1],
    '1623' => ['Química Analítica II',                                     1],
    '1625' => ['Operaciones Unitarias I',                                  1],
    '1626' => ['Tecnología de la Electricidad y Servicios Auxiliares',     1],
    '1627' => ['Tecnología de Materiales y Mecánica',                      1],
    '2632' => ['Servicios Auxiliares',                                     1],
    '2622' => ['Formulación y Evaluación de Proyectos',                    2],
    '1628' => ['Principio de Biotecnología',                               1],
    '1629' => ['Operaciones Unitarias II',                                 1],
    '1630' => ['Operaciones Unitarias III',                                1],
    '1631' => ['Procesos Unitarios',                                       1],
    '1639' => ['Gestión de la Calidad',                                    2],
    '1632' => ['Dinámica y Control de Procesos',                           1],
    '1634' => ['Industrias Químicas',                                      1],
    '1635' => ['Economía y Organización Industrial',                       2],
    '2623' => ['Tecnología de la Electricidad',                            1],
    '1638' => ['Proyecto Final',                                           1],
    '1908' => ['Seminario de Energías Renovables',                         1],
    '1208' => ['Inglés (Interpretación de Textos)',                        2],
]);

// ══════════════════════════════════════════════════════════════════════════
// 913 - LICENCIATURA EN ADMINISTRACIÓN
// ══════════════════════════════════════════════════════════════════════════
cargar($conn, 'Licenciatura en Administración', '913P5', '913', 5, [
    '0387' => ['Matemática I',                                   1],
    '1107' => ['Introducción al Conocimiento Científico',        2],
    '1127' => ['Sistemas Contables I',                           2],
    '0390' => ['Matemática II',                                  1],
    '1108' => ['Ciencia, Universidad y Sociedad',                2],
    '1133' => ['Administración I',                               2],
    '0901' => ['Análisis y Producción del Discurso (A)',          2],
    '1134' => ['Derecho Empresarial I',                          2],
    '1135' => ['Comportamiento Organizacional',                  2],
    '1136' => ['Administración II',                              2],
    '1137' => ['Economía I',                                     2],
    '1138' => ['Cálculo Financiero',                             2],
    '1139' => ['Sistemas Contables II',                          2],
    '1147' => ['Optativa / Electiva',                            2],
    '1221' => ['Estadística',                                    2],
    '1141' => ['Comercialización I',                             2],
    '1142' => ['Derecho Empresarial II',                         2],
    '1143' => ['Técnica Impositiva',                             2],
    '1151' => ['Informática para la Gestión',                    1],
    '1149' => ['Economía II',                                    2],
    '1150' => ['Costos',                                         2],
    '1152' => ['Gestión de Operaciones',                         2],
    '1153' => ['Administración Financiera',                      2],
    '1154' => ['Administración de Recursos Humanos',             2],
    '1155' => ['Modelos de Decisión',                            2],
    '1158' => ['Comercialización II',                            2],
    '1159' => ['Formulación y Evaluación de Proyectos',          2],
    '1160' => ['Administración III',                             2],
    '1168' => ['Seminario de Administración',                    2],
    '1145' => ['Nivel de Ofimática',                             1],
    '1367' => ['Optativa Higiene y Seguridad Industrial',        1],
    '1208' => ['Inglés (Interpretación de Textos)',              2],
    '1163' => ['Dirección General',                              2],
]);

// ══════════════════════════════════════════════════════════════════════════
// 914 - PROFESORADO EN GESTIÓN DE LAS ORGANIZACIONES
// ══════════════════════════════════════════════════════════════════════════
cargar($conn, 'Profesorado en Gestión de las Org.', '914P3', '914', 5, [
    '0387' => ['Matemática I',                                   1],
    '1107' => ['Introducción al Conocimiento Científico',        2],
    '1127' => ['Sistemas Contables I',                           2],
    '0390' => ['Matemática II',                                  1],
    '1108' => ['Ciencia, Universidad y Sociedad',                2],
    '1122' => ['Problemática Educativa',                         2],
    '0901' => ['Análisis y Producción del Discurso (A)',          2],
    '1134' => ['Derecho Empresarial I',                          2],
    '1137' => ['Economía I',                                     2],
    '1133' => ['Administración I',                               2],
    '1139' => ['Sistemas Contables II',                          2],
    '1221' => ['Estadística',                                    2],
    '0903' => ['Aprendizaje (A)',                                 2],
    '0929' => ['Enseñanza y Curriculum',                         2],
    '1135' => ['Comportamiento Organizacional',                  2],
    '1136' => ['Administración II',                              2],
    '1142' => ['Derecho Empresarial II',                         2],
    '1143' => ['Técnica Impositiva',                             2],
    '1138' => ['Cálculo Financiero',                             2],
    '1149' => ['Economía II',                                    2],
    '1141' => ['Comercialización I',                             2],
    '1151' => ['Informática para la Gestión',                    1],
]);

// ══════════════════════════════════════════════════════════════════════════
// 918 - LICENCIATURA EN COMUNICACIÓN SOCIAL
// ══════════════════════════════════════════════════════════════════════════
cargar($conn, 'Licenciatura en Comunicación Social', '918P1', '918', 9, [
    '0008' => ['Herramientas de Informática',                                      1],
    '0901' => ['Análisis y Producción del Discurso (A)',                            2],
    '1107' => ['Introducción al Conocimiento Científico',                          2],
    '1406' => ['Sociología General',                                               2],
    '1407' => ['Introducción a los Medios Masivos',                                2],
    '1108' => ['Ciencia, Universidad y Sociedad',                                  2],
    '1375' => ['Taller de Nuevas Tecnologías de la Comunicación',                  2],
    '1376' => ['Taller de Producción Escrita',                                     2],
    '1377' => ['Semiótica',                                                        2],
    '1374' => ['Teoría de la Comunicación I',                                      2],
    '1379' => ['Comunicación Gráfica y Taller I',                                  2],
    '1380' => ['Comunicación Radiofónica y Taller I',                              2],
    '1381' => ['Taller de Diseño Gráfico y Fotografía',                            2],
    '0439' => ['Historia Americana y Argentina Contemporánea',                     2],
    '1382' => ['Comunicación Gráfica y Taller II',                                 2],
    '1383' => ['Comunicación Radiofónica y Taller II',                             2],
    '1384' => ['Inglés I',                                                         2],
    '0444' => ['Lenguaje de los Medios de Comunicación',                           2],
    '1378' => ['Teoría de la Comunicación II',                                     2],
    '1386' => ['Comunicación Audiovisual y Taller I',                              2],
    '1387' => ['Optativa',                                                         2],
    '1388' => ['Comunicación Audiovisual y Taller II',                             2],
    '1389' => ['Optativa 2',                                                       2],
    '1390' => ['Metodología de la Investigación en Comunicación',                  2],
    '1391' => ['Inglés II',                                                        2],
    '1019' => ['Literatura de Masas',                                              2],
    '1392' => ['Opinión Pública',                                                  2],
    '1393' => ['Seminario de Tesis',                                               2],
    '1408' => ['Publicidad, Propaganda y Taller',                                  2],
    '1409' => ['Seminario de Investigación Periodística',                          2],
    '0442' => ['Comunicación Educativa',                                           2],
    '1385' => ['Seminario de Comunicación Estratégica',                            2],
    '1394' => ['Seminario de Pensamiento Contemporáneo',                           2],
    '1395' => ['Optativa 3',                                                       2],
    '1397' => ['Práctica Profesional',                                             2],
]);

echo "\n\n✓ Carga masiva finalizada.\n";
