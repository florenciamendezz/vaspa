<?php
require_once 'c:/xampp/htdocs/vaspa/modeloSistema/BDConexionSistema.Class.php';
$db = BDConexionSistema::getInstancia();
echo "--- PLANES ---\n";
$res = $db->query("SELECT * FROM plan");
while ($r = $res->fetch_assoc()) {
    print_r($r);
}
echo "--- ASIGNATURAS ---\n";
$res2 = $db->query("SELECT * FROM asignatura");
while ($r2 = $res2->fetch_assoc()) {
    print_r($r2);
}
