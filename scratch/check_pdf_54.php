<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';
$db = BDConexionSistema::getInstancia();
$res = $db->query("SELECT * FROM programa_pdf_detalle WHERE id = 54");
if ($res) {
    print_r($res->fetch_assoc());
} else {
    echo "No se encontró el registro 54\n";
}
