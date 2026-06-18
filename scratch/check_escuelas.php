<?php
$c = new mysqli('localhost','root','1234','bdgef_vaspa');
$c->set_charset('utf8');

echo "Escuelas:\n";
$r = $c->query('SELECT id, nombre FROM escuela LIMIT 10');
while($row = $r->fetch_object()) echo $row->id . ' - ' . $row->nombre . "\n";

echo "\nVer cómo insertan las asignaturas existentes (idEscuela, idProfesor):\n";
$r2 = $c->query('SELECT id, nombre, idEscuela, idProfesor FROM asignatura LIMIT 5');
while($row = $r2->fetch_object()) {
    echo "ID: $row->id | Escuela: $row->idEscuela | Profesor: $row->idProfesor | $row->nombre\n";
}

echo "\n¿Existe profesor con id=1?\n";
$r3 = $c->query("SELECT id FROM profesor WHERE id = 1")->fetch_object();
echo $r3 ? "SI (ID 1)\n" : "NO\n";
