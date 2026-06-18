<?php
$conn = new mysqli('localhost', 'root', '1234', 'bdgef_vaspa');
if ($conn->connect_error) die('Error: ' . $conn->connect_error);
$conn->set_charset('utf8');

// Materias del listado (codigo => nombre)
$materias = [
    1018 => 'Teoría y Análisis Literario',
    1019 => 'Literatura de Masas',
    1107 => 'Introducción al Conocimiento Científico',
    1234 => 'Gramática Española',
    1020 => 'Lingüística I',
    1021 => 'Literatura Griega I',
    1108 => 'Ciencia, Universidad y Sociedad',
    1122 => 'Problemática Educativa',
    901  => 'Análisis y Producción del Discurso  (A)',
    1022 => 'Literatura Griega II',
    1023 => 'Lengua y Cultura Latinas I',
    1564 => 'Literatura Española I',
    1025 => 'Literatura Francesa',
    1026 => 'Lengua y Culturas Latinas II',
    1027 => 'Taller de Escritura',
    1610 => 'Literatura Española II',
    903  => 'Aprendizaje  (A)',
    929  => 'Enseñanza y Curriculum',
    1030 => 'Literatura Latinoamericana I',
    1031 => 'Lingüística II',
    967  => 'Análisis Político y Organizacional del Sistema Educativo',
    1032 => 'Literatura Argentina I',
    1033 => 'Seminario de Teoría Literaria',
    1034 => 'Literatura Latinoamericana II',
    1035 => 'Literatura Inglesa y Norteamericana',
    1036 => 'Seminario de Lingüística',
    1037 => 'Literatura Argentina II',
    1267 => 'Didáctica Especial',
    1038 => 'Seminario de Literatura',
    1039 => 'Historia de la Lengua',
    1284 => 'Taller de Práctica Docente',
    421  => 'Idioma moderno (Francés o Inglés)',
];

// ID del departamento Ciencias Sociales
$idDepto = 2;

// Verificar carrera 001
$res = $conn->query("SELECT id, nombre FROM carrera WHERE id = '001'");
echo "=== CARRERA ===\n";
if ($row = $res->fetch_object()) {
    echo "OK - Carrera 001: " . $row->nombre . "\n";
} else {
    echo "NO EXISTE la carrera 001 (Profesorado en Letras)\n";
}

echo "\n=== VERIFICACIÓN MATERIAS ===\n";
$existentes = 0;
$faltantes = 0;
$sinDepto = 0;

foreach ($materias as $codigo => $nombre) {
    $res = $conn->query("SELECT id, nombre, idDepartamento FROM asignatura WHERE id = '$codigo'");
    if ($row = $res->fetch_object()) {
        $existentes++;
        if (!$row->idDepartamento || $row->idDepartamento == 0) {
            $sinDepto++;
            echo "[SIN DEPTO] $codigo - $row->nombre\n";
        }
    } else {
        $faltantes++;
        echo "[FALTANTE] $codigo - $nombre\n";
    }
}

echo "\nResumen: $existentes existentes, $sinDepto sin departamento, $faltantes faltantes\n";
