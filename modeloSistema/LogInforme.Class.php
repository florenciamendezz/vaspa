<?php
include_once "BDConexionSistema.Class.php";

class LogInforme {
    
    private $id;
    private $id_usuario;
    private $email_usuario;
    private $fecha_hora;
    private $tipo_informe;
    private $contenido;

    /**
     * Guarda un nuevo log de informe
     * @param int $id_usuario
     * @param string $email_usuario
     * @param string $tipo_informe
     * @param string $contenido (HTML o JSON)
     * @return bool
     */
    public static function guardarLog($id_usuario, $email_usuario, $tipo_informe, $contenido) {
        $conexion = BDConexionSistema::getInstancia();
        $fecha_hora = date('Y-m-d H:i:s');
        
        // Manejo de valores nulos o vacios
        $email_usuario = isset($email_usuario) ? $email_usuario : '';
        $id_usuario = !empty($id_usuario) ? (int)$id_usuario : 'NULL';
        
        // Escapar contenido para evitar errores en SQL (o inyeccion)
        $contenidoEscapado = $conexion->real_escape_string($contenido);
        $tipoEscapado = $conexion->real_escape_string($tipo_informe);
        // Cast a string para evitar Deprecated warning si viene null
        $emailEscapado = $conexion->real_escape_string((string)$email_usuario);
        
        $sql = "INSERT INTO log_informes (id_usuario, email_usuario, fecha_hora, tipo_informe, contenido) 
                VALUES ({$id_usuario}, '{$emailEscapado}', '{$fecha_hora}', '{$tipoEscapado}', '{$contenidoEscapado}')";
        
        if ($conexion->query($sql)) {
            return true;
        } else {
            // Opcional: loguear error en archivo php_error.log
            error_log("Error al guardar log informe: " . $conexion->error);
            return false;
        }
    }

    // Getters y Setters se pueden agregar si se necesita leer historial
}
?>
