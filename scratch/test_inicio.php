<?php
// 1. Simular el entorno de servidor
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['PHP_SELF'] = '/vaspa/vista/inicio.php';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Cambiar al directorio de la vista para que los paths relativos ../ resuelvan bien
chdir('C:/xampp/htdocs/vaspa/vista');

// 2. Iniciar sesión y simular al usuario Administrador
session_start();

require_once 'C:/xampp/htdocs/vaspa/modeloSistema/BDConexionSistema.Class.php';
require_once 'C:/xampp/htdocs/vaspa/lib/ControlAcceso.Class.php';

// Crear el objeto de usuario y guardarlo en la sesión
$usuario = new UsuarioSesion('luzmariagaraigarai@gmail.com', 'Administrador');
$_SESSION['usuario'] = $usuario;

echo "Simulando carga de vista/inicio.php para el Administrador con chdir...\n";

try {
    ob_start();
    include 'C:/xampp/htdocs/vaspa/vista/inicio.php';
    $output = ob_get_clean();
    echo "=== EJECUCION EXITOSA ===\n";
    echo "Longitud de la salida: " . strlen($output) . " bytes\n";
    echo "Primeros 400 caracteres de la salida:\n";
    echo substr(strip_tags($output), 0, 400) . "\n";
} catch (Throwable $t) {
    ob_get_clean();
    echo "!!! EXCEPCION O ERROR DE RUNTIME CAPTURADO !!!\n";
    echo "Clase: " . get_class($t) . "\n";
    echo "Mensaje: " . $t->getMessage() . "\n";
    echo "Archivo: " . $t->getFile() . "\n";
    echo "Línea: " . $t->getLine() . "\n";
    echo "Trace:\n" . $t->getTraceAsString() . "\n";
}
?>
