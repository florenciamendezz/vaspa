<?php
$c = new mysqli('localhost','root','1234','bdgef_vaspa');
$c->set_charset('utf8');

// Marcar como institucional
$c->query("UPDATE asignatura SET es_institucional = 1 WHERE id = '0901'");
echo "Filas afectadas: " . $c->affected_rows . "\n";

// Verificar resultado
$r = $c->query("SELECT id, nombre, es_institucional FROM asignatura WHERE id = '0901'")->fetch_object();
echo "ID: $r->id | $r->nombre | es_institucional: $r->es_institucional\n";

// Verificar también si hay otras materias que deberían ser institucionales
// (las que aparecen en múltiples carreras son candidatas)
echo "\n--- Materias que aparecen en más de una carrera ---\n";
$r2 = $c->query("
    SELECT a.id, a.nombre, a.es_institucional, COUNT(ca.idCarrera) as cant_carreras
    FROM asignatura a
    JOIN carrera_asignatura ca ON ca.idAsignatura = a.id
    GROUP BY a.id, a.nombre, a.es_institucional
    HAVING COUNT(ca.idCarrera) > 1
    ORDER BY cant_carreras DESC
");
while($row = $r2->fetch_object()) {
    $inst = $row->es_institucional ? '✓ institucional' : '  NO institucional';
    echo "[$inst] $row->id - $row->nombre (en $row->cant_carreras carreras)\n";
}
