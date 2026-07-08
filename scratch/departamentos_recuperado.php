"<?php
include_once '../lib/ControlAcceso.Class.php';
require_once '../modeloSistema/BDConexionSistema.Class.php';

// Validar que el usuario tenga rol de Administrador o Vinculación Académica
$usuario = $_SESSION['usuario'];
$rol = $usuario->roles[0]->nombre;
if ($rol != PermisosSistema::ROL_ADMIN && $rol != PermisosSistema::ROL_VINCULACION_ACADEMICA) {
    header("Location: inicio.php");
    exit();
}

$db = BDConexionSistema::getInstancia();

// Obtener los 2 departamentos oficiales
$resDeptos = $db->query("SELECT * FROM departamento ORDER BY nombre ASC");
$departamentos = [];
while ($row = $resDeptos->fetch_assoc()) {
    $departamentos[] = $row;
}

// Obtener todas las asignaturas
$resAsignaturas = $db->query("SELECT id, nombre, idDepartamento FROM asignatura ORDER BY nombre ASC");
$asignaturas = [];
while ($row = $resAsignaturas->fetch_assoc()) {
    $asignaturas[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../lib/bootstrap-4.1.1-dist/css/bootstrap.css" />
    <link rel="stylesheet" href="../lib/open-iconic-master/font/css/open-iconic-bootstrap.css" />
    <link rel="stylesheet" href="../lib/datatable/dataTables.bootstrap4.min.css" />
    <script type="text/javascript" src="../lib/JQuery/jquery-3.3.1.js"></script>
    <script type="text/javascript" src="../lib/bootstrap-4.1.1-dist/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="../lib/datatable/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="../lib/datatable/dataTables.bootstrap4.min.js"></script>      
    <title><?= Constantes::NOMBRE_SISTEMA; ?> - Asociar Departamentos</title>
    <style>
        body {
            background-color: #f3f4f6;
            color: #1f2937;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        .container
<truncated 9844 bytes>