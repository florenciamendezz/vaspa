<?php
require_once 'modeloSistema/BDConexionSistema.Class.php';

// Ver planes activos de Analista de Sistemas (016)
$sql = "SELECT id, anio_inicio, anio_fin FROM bdgef_vaspa.plan WHERE idCarrera = '016'";
$resultado = BDConexionSistema::getInstancia()->query($sql);
while($row = $resultado->fetch_assoc()){
    print_r($row);
}
