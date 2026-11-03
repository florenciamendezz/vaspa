<?php
/**
 * Clase Env - Cargador liviano y seguro de variables de entorno (.env) para VASPA.
 * 
 * Permite leer pares CLAVE=VALOR desde un archivo .env, poblar $_ENV, $_SERVER
 * y putenv(), soportando comentarios, comillas y valores por defecto.
 */
class Env {
    private static $cargado = false;
    private static $variables = [];

    /**
     * Carga el archivo .env si no ha sido cargado previamente.
     * 
     * @param string|null $ruta Ruta absoluta o relativa al archivo .env
     * @param bool $sobrescribir Si es false, no pisa variables ya existentes en $_ENV o getenv()
     * @return bool True si se cargó exitosamente, false en caso contrario
     */
    public static function cargar($ruta = null, $sobrescribir = false) {
        if (self::$cargado && $ruta === null) {
            return true;
        }

        if ($ruta === null) {
            $ruta = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        }

        if (!file_exists($ruta) || !is_readable($ruta)) {
            return false;
        }

        $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lineas === false) {
            return false;
        }

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            // Ignorar líneas vacías o comentarios
            if ($linea === '' || strpos($linea, '#') === 0 || strpos($linea, ';') === 0) {
                continue;
            }

            // Separar únicamente por el primer caracter '='
            $partes = explode('=', $linea, 2);
            if (count($partes) !== 2) {
                continue;
            }

            $nombre = trim($partes[0]);
            $valor = trim($partes[1]);

            // Quitar comillas si las tuviera (dobles o simples)
            if (strlen($valor) >= 2) {
                $primer = $valor[0];
                $ultimo = substr($valor, -1);
                if (($primer === '"' && $ultimo === '"') || ($primer === "'" && $ultimo === "'")) {
                    $valor = substr($valor, 1, -1);
                }
            }

            // Convertir literales booleanos y nulos comunes
            $valorLower = strtolower($valor);
            if ($valorLower === 'true' || $valorLower === '(true)') {
                $valor = 'true';
            } elseif ($valorLower === 'false' || $valorLower === '(false)') {
                $valor = 'false';
            } elseif ($valorLower === 'null' || $valorLower === '(null)') {
                $valor = '';
            }

            self::$variables[$nombre] = $valor;

            // Inyectar en $_ENV, $_SERVER y putenv si corresponde
            if ($sobrescribir || !isset($_ENV[$nombre])) {
                $_ENV[$nombre] = $valor;
            }
            if ($sobrescribir || !isset($_SERVER[$nombre])) {
                $_SERVER[$nombre] = $valor;
            }
            if ($sobrescribir || getenv($nombre) === false) {
                putenv("{$nombre}={$valor}");
            }
        }

        self::$cargado = true;
        return true;
    }

    /**
     * Obtiene el valor de una variable de entorno.
     * 
     * @param string $clave Nombre de la variable
     * @param mixed $default Valor de fallback si no existe
     * @return mixed
     */
    public static function get($clave, $default = null) {
        if (!self::$cargado) {
            self::cargar();
        }

        if (array_key_exists($clave, self::$variables)) {
            return self::$variables[$clave];
        }
        if (isset($_ENV[$clave])) {
            return $_ENV[$clave];
        }
        if (isset($_SERVER[$clave])) {
            return $_SERVER[$clave];
        }
        $val = getenv($clave);
        if ($val !== false) {
            return $val;
        }

        return $default;
    }

    /**
     * Retorna si el entorno actual es local/desarrollo/pruebas.
     * 
     * @return bool
     */
    public static function esLocal() {
        $env = strtolower(self::get('APP_ENV', 'local'));
        return in_array($env, ['local', 'development', 'testing', 'dev'], true);
    }
}
