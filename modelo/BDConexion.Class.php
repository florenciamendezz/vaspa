<?php
require_once __DIR__ . '/../lib/Env.Class.php';

/**
 * Description of BDConexion
 * 
 * Esta clase implementa la conexión a una base de datos mediante el patrón Singleton.
 *
 * @author Eder dos Santos <esantos@uarg.unpa.edu.ar>
 * 
 * @uses mysqli Libería estándar de PHP para acceder a bases de datos MySQL
 * @see https://es.wikipedia.org/wiki/Singleton
 * 
 */
class BDConexion extends mysqli {

    private $host, $usuario, $contrasenia, $schema, $puerto;
    public static $instancia;
    
    function __construct() {
        Env::cargar();
        $this->host = Env::get('DB_HOST', '127.0.0.1');
        $this->usuario = Env::get('DB_USER', 'root');
        $this->contrasenia = Env::get('DB_PASS', '');
        $this->schema = Env::get('DB_NAME', 'bdgef_vaspa');
        $this->puerto = (int) Env::get('DB_PORT', 3306);

        parent::__construct($this->host, $this->usuario, $this->contrasenia, $this->schema, $this->puerto);
        parent::set_charset(Env::get('DB_CHARSET', 'utf8'));
        if ($this->connect_errno) {
            throw new Exception("Error de Conexion a la Base de Datos", $this->connect_errno);
        }
    }
    
    /**
     * @return BDConexion
     */
    public static function getInstancia() {
        if (!self::$instancia instanceof self) {
            try {
                self::$instancia = new self;
            } catch (Exception $e) {
                error_log("Error de Conexion a la Base de Datos: " . $e->getMessage());
                die("Error de Conexion a la Base de Datos: " . $e->getCode() . ".");
            }
        }
        return self::$instancia;
    }

    /**
     * Ejecuta una consulta SELECT parametrizada y retorna el mysqli_result resultante o false.
     * Utiliza execute_query nativo de PHP 8.2 con manejo interno de excepciones.
     * 
     * @param string $sql Sentencia SQL con placeholders (?)
     * @param array $params Array indexado con los valores de los parámetros
     * @return mysqli_result|false
     */
    public function queryPrepared($sql, array $params = []) {
        try {
            $resultado = $this->execute_query($sql, $params);
            return ($resultado instanceof mysqli_result) ? $resultado : false;
        } catch (Throwable $e) {
            error_log("Error en queryPrepared: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }

    /**
     * Ejecuta una sentencia INSERT/UPDATE/DELETE parametrizada.
     * 
     * @param string $sql Sentencia SQL con placeholders (?)
     * @param array $params Array indexado con los valores de los parámetros
     * @return bool True si la ejecución fue exitosa, false en caso contrario.
     */
    public function executePrepared($sql, array $params = []) {
        try {
            $resultado = $this->execute_query($sql, $params);
            return ($resultado !== false);
        } catch (Throwable $e) {
            error_log("Error en executePrepared: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }

}
