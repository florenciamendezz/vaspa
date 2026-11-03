<?php
require_once 'c:/xampp/htdocs/vaspa/modeloSistema/BDConexionSistema.Class.php';
$db = BDConexionSistema::getInstancia();

// Asignatura 0174 - Programación I (Escuela 8, Depto 1, no institucional)
$q1 = $db->query("INSERT IGNORE INTO asignatura (id, nombre, idDepartamento, idEscuela, es_institucional) VALUES ('0174', 'Programación I', 1, 8, 0)");
if (!$q1) echo "Err 0174: " . $db->error . "\n";

// Asignatura 1108 - Ciencia, Universidad y Sociedad (Escuela 4, Depto 2, institucional)
$q2 = $db->query("INSERT IGNORE INTO asignatura (id, nombre, idDepartamento, idEscuela, es_institucional) VALUES ('1108', 'Ciencia, Universidad y Sociedad', 2, 4, 1)");
if (!$q2) echo "Err 1108: " . $db->error . "\n";

// Planes para carrera 016
$q3 = $db->query("INSERT IGNORE INTO plan (id, anio_inicio, idCarrera, anio_fin) VALUES ('016P5', 2013, '016', 2023)");
if (!$q3) echo "Err 016P5: " . $db->error . "\n";

$q4 = $db->query("INSERT IGNORE INTO plan (id, anio_inicio, idCarrera, anio_fin) VALUES ('016P9', 2024, '016', NULL)");
if (!$q4) echo "Err 016P9: " . $db->error . "\n";

echo "OK_SEED_BASE\n";
