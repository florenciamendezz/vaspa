<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver las materias que corresponden al profesor 5 (Sandra Casas, accesoriosperlados@gmail.com) según obtenerAsignaturasDePlanVigente
require_once 'modeloSistema/Profesor.Class.php';
$prof = new Profesor(5);
$asigs = $prof->obtenerAsignaturasDePlanVigente();
foreach ($asigs as $a) {
    echo "ID: " . $a->getId() . " - Nombre: " . $a->getNombre() . " - Escuela: " . $a->getIdEscuela() . "\n";
}
