<?php
$c = new mysqli('localhost','root','1234','bdgef_vaspa');
$c->set_charset('utf8');

// Ver un profesor válido
echo "Profesores disponibles:\n";
$r = $c->query('SELECT id, nombre FROM profesor LIMIT 5');
while($row = $r->fetch_object()) echo $row->id . ' - ' . $row->nombre . "\n";

// Ver si idProfesor acepta NULL
echo "\nDescribir idProfesor:\n";
$d = $c->query("DESCRIBE asignatura");
while($row = $d->fetch_object()) {
    if ($row->Field == 'idProfesor') {
        echo "idProfesor - Null: $row->Null - Default: $row->Default\n";
    }
}

// Ver si idEscuela acepta NULL
echo "\nDescribir idEscuela:\n";
$d2 = $c->query("DESCRIBE asignatura");
while($row = $d2->fetch_object()) {
    if ($row->Field == 'idEscuela') {
        echo "idEscuela - Null: $row->Null - Default: $row->Default\n";
    }
}
