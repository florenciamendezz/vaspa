<?php
include_once 'modeloSistema/BDConexionSistema.Class.php';

$email = 'esstefaniamendez@gmail.com';
$sql = "SELECT * FROM profesor WHERE email = '$email'";
$res = BDConexionSistema::getInstancia()->query($sql);

echo "<h2>Checking for Duplicate Emails: $email</h2>";
echo "Num Rows: " . $res->num_rows . "<br>";

while ($row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . " - Name: " . $row['nombre'] . " " . $row['apellido'] . "<br>";
}
?>
