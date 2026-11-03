<?php
require_once 'c:/xampp/htdocs/vaspa/modeloSistema/BDConexionSistema.Class.php';
$db = BDConexionSistema::getInstancia();

$db->query("SET FOREIGN_KEY_CHECKS = 0");

// Eliminar programas de prueba creados durante los tests
$db->query("DELETE FROM programa_pdf_detalle WHERE id_asignatura IN ('0174', '1108') AND fecha_carga >= CURDATE()");
$db->query("DELETE FROM programa WHERE idAsignatura IN ('0174', '1108') AND fechaCarga >= CURDATE()");

// Eliminar carreras de prueba
$db->query("DELETE FROM carrera WHERE id IN ('990', '991') OR nombre LIKE 'TEST_E2E_%'");

// Eliminar planes de prueba
$db->query("DELETE FROM plan WHERE id IN ('016PT', '990PT') OR id LIKE 'TEST_%'");

// Eliminar usuario_rol de prueba
$db->query("DELETE FROM usuario_rol WHERE id_usuario IN (SELECT id FROM usuario WHERE email IN ('testuserqa@uarg.unpa.edu.ar', 'test_e2e_user@uarg.unpa.edu.ar', 'qatest@uarg.unpa.edu.ar') OR nombre LIKE 'TEST_E2E_%')");

// Eliminar usuarios de prueba
$db->query("DELETE FROM usuario WHERE email IN ('testuserqa@uarg.unpa.edu.ar', 'test_e2e_user@uarg.unpa.edu.ar', 'qatest@uarg.unpa.edu.ar') OR nombre LIKE 'TEST_E2E_%'");

// Eliminar docentes de prueba
$db->query("DELETE FROM profesor WHERE email IN ('docenteqa@uarg.unpa.edu.ar') OR apellido = 'DocenteTest'");

$db->query("SET FOREIGN_KEY_CHECKS = 1");

echo "OK_CLEAN_TEST\n";
