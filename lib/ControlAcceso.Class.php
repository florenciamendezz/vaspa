<?php
require_once __DIR__ . '/Env.Class.php';
include_once __DIR__ . '/Constantes.Class.php';
include_once __DIR__ . '/../modelo/BDColeccionGenerica.Class.php';

/**
 * Clase de constantes para el uso del sistema de Roles y Permisos.
 * Deben coincidir con los permisos de la base de datos.
 * 
 * @author Eder dos Santos <esantos@uarg.unpa.edu.ar>
 */
class PermisosSistema {

    /**
     * 
     * Permisos genéricos del sistema.
     * Se usan en funciones comunes:
     *  - Salir: Para usuarios logueados.
     *  - Ingresar: Para usuarios no logueados.
     * 
     */
    const PERMISO_SALIR = "Salir";
    const PERMISO_LOGIN = "Ingresar";

    /**
     * 
     * Permisos específicos.
     * Son aquellos permisos específicos, que se definen de acuerdo a los casos de uso.
     * 
     *      Nota: Se pueden definir nuevos permisos con un mayor nivel de especificad. 
     *      Ejemplos: PERMISO_USUARIO_ALTA, PERMISO_ROL_BAJA.
     * 
     */
    const PERMISO_USUARIOS = "Usuarios";
    const PERMISO_PERMISOS = "Permisos";
    const PERMISO_ROLES = "Roles";
    
    /* 
     * Observacion: no se agregaron como permisos el visualizar Programa y el de Plan
     * ya esas funcionalidades las puede realizar el invitado, no necesita iniciar
     * sesion en el sistema.
     */
    // Permisos para ABM del sistema (Rol "Secretario Academico" y "Admin")
    const PERMISO_CARRERAS = "Carreras";

    const PERMISO_ASIGNATURAS = "Asignaturas";
    const PERMISO_PROFESORES = "Profesores";
    
    // Permisos para el Rol "Secretario Academico" y "Admin"
    const PERMISO_SUBIR_PROGRAMA_FIRMADO = "Subir Programa Firmado";

    const PERMISO_SEGUIR_PROGRAMA = "Seguir Programa";
    const PERMISO_ENVIAR_NOTIFICACION = "Enviar Notificacion";
    const PERMISO_CARGA_MASIVA_PROGRAMAS = "Carga Masiva Programas";
    const PERMISO_GENERAR_INFORME_GERENCIAL = "Generar Informe Gerencial";
    const PERMISO_VER_VIGENCIA_PROGRAMAS = "Ver Vigencia de Programas";
    
    // Permisos para el Rol "Profesor"
    const PERMISO_GENERAR_PDF = "Generar Programa PDF"; // tambien lo puede relizar "Secretario Academico"
    const PERMISO_GESTIONAR_PROGRAMA = "Gestionar Programa";
    const PERMISO_GESTIONAR_BIBLIOGRAFIA = "Gestionar Bibliografia";
    
    // Permiso para el Rol "Director de Departamento"
    const PERMISO_REVISAR_PROGRAMA = "Revisar Programa"; // tambien lo puede relizar "Secretario Academico"
    
    /**
     * Roles del Sistema.
     * La definición de los todos roles es Opcional, pero se requiere cargar un rol Estandar para el autoregistro de Usuarios.
     * 
     */
    //const ROL_ESTANDAR = 'Usuario Comun'; // Eliminar este ROL
    const ROL_ADMIN = "Administrador";
    const ROL_VINCULACION_ACADEMICA = "Vinculación Académica";
    const ROL_DIRECTOR_DEPARTAMENTO = "Director de Departamento";
    const ROL_DIRECTOR_ESCUELA = "Director de Escuela";
    const ROL_PROFESOR = "Profesor";
    //const ROL_INVITADO = "Invitado";

}

class PermisoSesion {

    public $id;
    public $nombre;

    /**
     *
     * @var mysqli_result 
     */
    protected $datos;

}

class RolSesion {

    public $id;
    public $nombre;

    /**
     *
     * @var PermisoSesion[]
     */
    public $permisos = array();

    /**
     *
     * @var mysqli_result 
     */
    protected $datos;

    /**
     * @todo [12/07/2017] Capturar excepciones de BD para la llamada a setPermisos.
     */
    public function __construct() {
        $this->setPermisos();
    }

    public function setPermisos() {
        $idRol = intval($this->id);
        $db = BDConexion::getInstancia();
        $stmt = $db->prepare("SELECT p.id, p.nombre FROM " . Constantes::BD_USERS . ".permiso p "
                . "JOIN " . Constantes::BD_USERS . ".rol_permiso rp ON p.id = rp.id_permiso "
                . "WHERE rp.id_rol = ?");
        if (!$stmt) {
            error_log("Error al preparar setPermisos: " . $db->error);
            return;
        }
        $stmt->bind_param("i", $idRol);
        $stmt->execute();
        $this->datos = $stmt->get_result();
        if (!$this->datos) {
            $stmt->close();
            error_log("Error al obtener permisos para rol ID: " . $idRol);
            return;
        }
        for ($x = 0; $x < $this->datos->num_rows; $x++) {
            $this->permisos[] = $this->datos->fetch_object("PermisoSesion");
        }
        $stmt->close();
    }

}

class UsuarioSesion {

    public $id;
    public $email;
    public $nombre;

    /**
     *
     * @var mysqli_result 
     */
    protected $datos;

    /**
     *
     * @var RolSesion[] 
     */
    public $roles;

    /**
     * 
     * @param String $email_
     * @param String $nombre_
     * 
     * @throws Exception
     * @since 2.0.1 27/08/2018 - Nueva implementacion del autoregisto de usuarios. Corrige incompatibilidad con distintas versiones de PHP.
     * @todo 27/08/2018 Hacer refactoring Extract Method de la busqueda de usuario en la BD.
     * 
     * 
     */
    function __construct($email_, $nombre_) {

        $this->email = $email_;
        $this->nombre = $nombre_;

        try {
            $this->id = $this->buscarUsuarioBd();
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage(), $ex->getCode());
        }
        
        // Si no se encuentra registrado el usuario
        
//        if (!$this->id){
//            $_SESSION['msgLogin'] = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
//                                        <strong>Holy guacamole!</strong> You should check in on some of those fields below.
//                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
//                                          <span aria-hidden="true">&times;</span>
//                                        </button>
//                                      </div>';
//            echo "<b>No existe el usuario con el mail: $email_</b>";
////            try {
////                echo '<br>voy a registrar un nuevo usuario<br>';
////                $this->registrarUsuario();
////            } catch (Exception $ex) {
////                throw new Exception($ex->getMessage(), $ex->getCode());
////            }
//        }
        
        //echo $_SESSION['msgLogin'];
        //var_dump($_SESSION);
        //echo '<br>';
        //var_dump($this->id);
        //exit();
        
        if ($this->id){    
        $this->setRoles();}

    }

    public function buscarUsuarioBd() {
        $db = BDConexion::getInstancia();
        $stmt = $db->prepare("SELECT id, nombre FROM " . Constantes::BD_USERS . ".usuario WHERE email = ? LIMIT 1");
        if (!$stmt) {
            error_log("Error al preparar buscarUsuarioBd: " . $db->error);
            return null;
        }
        $stmt->bind_param("s", $this->email);
        $stmt->execute();
        $this->datos = $stmt->get_result();
        $row = $this->datos ? $this->datos->fetch_assoc() : null;
        $stmt->close();
        if ($row) {
            // Sobreescribir el nombre con el registrado en la BD, no el que viene de Google
            $this->nombre = $row['nombre'];
            return (int) $row['id'];
        }
        return null;
    }

    /**
     * 
     * Crear un usuario estándar a partir del login con una cuenta Google.
     * Registra los datos en la base de datos. Tablas: usuario, usuario_google, usuario_rol.
     * 
     * @return Int id del usuario creado en la base de datos.
     * 
     */
    function registrarUsuario() {
        $db = BDConexion::getInstancia();
        $db->autocommit(false);
        $db->begin_transaction();

        $stmtIns = $db->prepare("INSERT INTO " . Constantes::BD_USERS . ".usuario (id, nombre, email) VALUES (NULL, ?, ?)");
        if (!$stmtIns) {
            $db->rollback();
            throw new Exception("Error al preparar registrarUsuario: " . $db->error);
        }
        $stmtIns->bind_param("ss", $this->nombre, $this->email);
        if (!$stmtIns->execute()) {
            $stmtIns->close();
            $db->rollback();
            throw new Exception("Error al insertar usuario en registrarUsuario: " . $db->error);
        }
        $this->id = (int) $db->insert_id;
        $stmtIns->close();

        $rolEstandar = PermisosSistema::ROL_ESTANDAR;
        $stmtRol = $db->prepare("INSERT INTO " . Constantes::BD_USERS . ".usuario_rol (id_usuario, id_rol) "
                . "SELECT ?, id FROM " . Constantes::BD_USERS . ".rol WHERE nombre = ?");
        if (!$stmtRol) {
            $db->rollback();
            throw new Exception("Error al preparar usuario_rol en registrarUsuario: " . $db->error);
        }
        $stmtRol->bind_param("is", $this->id, $rolEstandar);
        if (!$stmtRol->execute()) {
            $stmtRol->close();
            $db->rollback();
            throw new Exception("Error al asignar rol en registrarUsuario: " . $db->error);
        }
        $stmtRol->close();

        $db->commit();
        $db->autocommit(true);
    }

    public function setRoles() {
        $idUsuario = intval($this->id);
        $db = BDConexion::getInstancia();
        $stmt = $db->prepare("SELECT r.id, r.nombre "
                . "FROM " . Constantes::BD_USERS . ".usuario u "
                . "JOIN " . Constantes::BD_USERS . ".usuario_rol ur ON (u.id = ur.id_usuario) "
                . "JOIN " . Constantes::BD_USERS . ".rol r ON (r.id = ur.id_rol) "
                . "WHERE u.id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $idUsuario);
            $stmt->execute();
            $this->datos = $stmt->get_result();
            if ($this->datos) {
                for ($x = 0; $x < $this->datos->num_rows; $x++) {
                    $this->roles[] = $this->datos->fetch_object("RolSesion");
                }
            }
            $stmt->close();
        }
    }

}

/**
 * 
 * Clase para mantener control de acceso al sistema.
 * @author Eder dos Santos <esantos@uarg.unpa.edu.ar>
 * 
 */
class ControlAcceso {

    public $datos;
    public $ubicacion;

    /**
     * 
     * @since v2.0 2017-08-14
     * Desactiva el autoregistro de usuarios.
     * 
     * @author Eder dos Santos <esantos@uarg.unpa.edu.ar>
    /**
     * Determina si el entorno actual corresponde a desarrollo local o testing.
     * Aplica política 'Fail-Closed': si no está definido o hay dudas, asume 'production'.
     * 
     * @return bool
     */
    public static function esEntornoPruebas(): bool {
        Env::cargar();
        return Env::esLocal();
    }

    function __construct() {

        $this->ubicacion = Constantes::SERVER . $_SERVER["PHP_SELF"];


        /**
         * Verificación Inicial del Usuario caso la página no sea el index.
         */
        if ($this->ubicacion != Constantes::HOMEURL) {
            unset($_SESSION["HTTP_REFERER"]);
            self::verificaLogin();
        } else {
            $_SESSION["HTTP_REFERER"] = Constantes::HOMEURL;
        }

        /**
         * Crea la sesión del Usuario caso la página de origen de los datos pasados por formulario sea el index.
         */
        if (isset($_SESSION["HTTP_REFERER"]) && $_SESSION["HTTP_REFERER"] == Constantes::HOMEURL && isset($_POST['email'])) {
            if (!self::esEntornoPruebas()) {
                // En producción se bloquea de forma tajante el bypass por POST directo
                error_log("[SEGURIDAD][SEC-002] Intento no autorizado de login de pruebas bloqueado en entorno no local. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconocida'));
                http_response_code(403);
                $_SESSION['auth_error'] = true;
                unset($_SESSION['usuario']);
                header("Location: " . Constantes::HOMEURL);
                exit;
            }

            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_DEFAULT) ?? '';

            try {
                $this->creaSesion($email, $nombre);
            } catch (Exception $e) {
                error_log("Error en creaSesion: " . $e->getMessage());
            }
            $this->redireccionaIndex();
        }

        /**
         * Luego de loguear, redireccion al index correspondiente a cada usuario.
         */
//        if (
//                ($this->ubicacion == Constantes::HOMEURL) &&
//                (isset($_SESSION['usuario'])) &&
//                (is_a($_SESSION['usuario'], 'UsuarioGoogle'))
//        ) {
//            $this->redireccionaIndex();
//        }
    }

    /**
     * 
     * Verifica si el usuario posee un permiso y en caso contrario lo redirecciona a la Home.
     * 
     * @param String $permiso_
     * @return void 
     * 
     * @author Eder dos Santos <esantos@uarg.unpa.edu.ar>
     * 
     */
    static function requierePermiso($permiso_) {
        if (!self::verificaPermiso($permiso_, $_SESSION['usuario'])) {
            header("Location: " . Constantes::HOMEURL);
            exit;
        }
    }

    /**
     * 
     * Verifica si un usuario posee un permiso específico.
     * @static
     * 
     * @param String $permiso_
     * @param UsuarioSesion $Usuario Obtenido de la Sesion
     * @return Boolean Description
     * 
     */
    static function verificaPermiso($permiso_) {
        $Usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
        if ($Usuario == null || !isset($Usuario->roles) || !is_array($Usuario->roles)) {
            return false;
        }
        foreach ($Usuario->roles as $Rol) {
            if (isset($Rol->permisos) && is_array($Rol->permisos)) {
                foreach ($Rol->permisos as $Permiso) {
                    if ($permiso_ == $Permiso->nombre) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Verifica si el usuario está logueado en el sistema (cargado en la sesión)
     * @static
     * 
     * @return mixed Redirecciona a la Home del sistema caso el usuario no esté logueado.
     */
    static function verificaLogin() {
        if (!isset($_SESSION['usuario']) || (!is_a($_SESSION['usuario'], "UsuarioSesion"))) {
            header("Location: " . Constantes::HOMEURL);
            exit;
        }
    }

    /**
     * 
     * @param type $email_
     * @param type $metodo_
     * @static
     * 
     * @todo [15/06/2017] El método está pensado para instanciar usuarios Google. Se debe generalizar.
     * @since v. 2.0 2017-08-14 - El método deja de ser estático. Autoregistro desactivado.
     * 
     * @author Eder dos Santos <esantos@uarg.unpa.edu.ar>
     * 
     */
    function creaSesion($email_, $nombre_ = null) {
        try {
            $Usuario = new UsuarioSesion($email_, $nombre_);
            // $Usuario->buscarUsuarioBd();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        
        /* la sesion se asigna cuando el usuario existe */
        if ($Usuario->id != NULL) {
            session_regenerate_id(true);
            $_SESSION['usuario'] = $Usuario;
            $_SESSION['auth_time'] = time();
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            unset($_SESSION['auth_error']);
        } else {
            unset($_SESSION['usuario']);
            $_SESSION['auth_error'] = true;
        }
        
        //$_SESSION['usuario'] = $Usuario;
    }

    /**
     * 
     */
    function redireccionaIndex() {
//        $this->ubicacion = Constantes::HOMEAUTH;
//        header("Location: {$this->ubicacion}");
        
        // Obtenemos el primer Rol del Usuario, por ahora se considera que un usuario solo puede tener un solo rol
        // Se debe modificar el ABM USUARIOS en caso de que sea asi
        if (isset($_SESSION['usuario']) && $_SESSION['usuario'] != ""){
            $Usuario = $_SESSION['usuario'];
            
            if (isset($Usuario->roles) && is_array($Usuario->roles) && count($Usuario->roles) > 0) {
                // Obtenemos el nombre del ROL del usuario
                $rol = $Usuario->roles[0]->nombre;

                // De acuerdo al ROL del Usuario lo derivamos a su pantalla principal.
                if ($rol == PermisosSistema::ROL_ADMIN){
                    $this->ubicacion = Constantes::HOMEAUTH;
                    header("Location: {$this->ubicacion}");
                    exit;
                }
                if ($rol == PermisosSistema::ROL_VINCULACION_ACADEMICA){
                    $this->ubicacion = Constantes::HOME_INICIO;
                    header("Location: {$this->ubicacion}");
                    exit;
                }
                if ($rol == PermisosSistema::ROL_PROFESOR){
                    $this->ubicacion = Constantes::HOME_INICIO;
                    header("Location: {$this->ubicacion}");
                    exit;
                }
                if ($rol == PermisosSistema::ROL_DIRECTOR_DEPARTAMENTO){
                    $this->ubicacion = Constantes::HOME_INICIO;
                    header("Location: {$this->ubicacion}");
                    exit;
                }
                if ($rol == PermisosSistema::ROL_DIRECTOR_ESCUELA){
                    $this->ubicacion = Constantes::HOME_INICIO;
                    header("Location: {$this->ubicacion}");
                    exit;
                }
            } else {
                header("Location: " . Constantes::HOMEURL);
                exit;
            }
        }
        
    }

    /**
     * Configura el reporte de errores y cabeceras de seguridad globales según el entorno (.env).
     */
    public static function configurarEntorno(): void {
        Env::cargar();
        $debug = Env::get('APP_DEBUG', false);
        $esDebug = ($debug === true || $debug === 'true' || $debug === '1');

        if ($esDebug) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
        } else {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
        }
        ini_set('log_errors', '1');

        // Cabeceras HTTP de seguridad (defensa en profundidad a nivel aplicación)
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
    }

    /**
     * Inicia la sesión PHP con parámetros seguros de cookies (HttpOnly, SameSite, Secure condicional).
     */
    public static function iniciarSesionSegura(): void {
        if (session_status() === PHP_SESSION_NONE) {
            $esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                       (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Lax');
            if ($esHttps) {
                ini_set('session.cookie_secure', '1');
            }

            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => $esHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            session_start();
        }
    }

    /**
     * Retorna el token CSRF activo de la sesión o genera uno nuevo si no existe.
     * @return string Token CSRF criptográficamente seguro de 64 caracteres.
     */
    public static function obtenerCsrfToken(): string {
        self::iniciarSesionSegura();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Retorna el campo HTML input oculto para incrustar en formularios POST.
     * @return string Tag input hidden con el token CSRF.
     */
    public static function campoCsrf(): string {
        $token = self::obtenerCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '" />';
    }

    /**
     * Valida el token CSRF recibido contra el token guardado en sesión usando comparación en tiempo constante.
     * @param string|null $token Token a verificar (opcional, si es null se extrae de POST o cabecera HTTP).
     * @return bool True si el token coincide exactamente, false en caso contrario.
     */
    public static function validarCsrfToken(?string $token = null): bool {
        self::iniciarSesionSegura();
        $tokenSesion = $_SESSION['csrf_token'] ?? '';
        if (empty($tokenSesion)) {
            return false;
        }

        if ($token === null) {
            if (!empty($_POST['csrf_token'])) {
                $token = (string)$_POST['csrf_token'];
            } elseif (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
                $token = (string)$_SERVER['HTTP_X_CSRF_TOKEN'];
            }
        }

        if (empty($token)) {
            return false;
        }

        return hash_equals($tokenSesion, $token);
    }

    /**
     * Middleware estricto para procesadores de formulario: rechaza peticiones POST que no presenten token CSRF válido.
     * @return void
     */
    public static function requiereCsrf(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::validarCsrfToken()) {
                http_response_code(403);
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['error' => 'Error de validación CSRF. Petición no autorizada.']);
                } else {
                    header('Content-Type: text/html; charset=utf-8');
                    echo '<div style="font-family:sans-serif;padding:20px;color:#721c24;background:#f8d7da;border:1px solid #f5c6cb;border-radius:4px;margin:20px;">' .
                         '<h3>403 Prohibido</h3>' .
                         '<p>La validación de seguridad Anti-CSRF ha fallado o su sesión ha caducado. Por favor, vuelva a cargar la página e intente nuevamente.</p>' .
                         '<a href="javascript:history.back()">Volver atrás</a>' .
                         '</div>';
                }
                exit;
            }
        }
    }

}
ControlAcceso::configurarEntorno();
ControlAcceso::iniciarSesionSegura();
$ControlAcceso = new ControlAcceso();

if (!function_exists('e')) {
    /**
     * Helper universal de escape contextual para prevención de Cross-Site Scripting (XSS).
     *
     * @param mixed $valor Valor a escapar para contexto HTML.
     * @return string Cadena saneada con codificación de entidades UTF-8.
     */
    function e($valor): string {
        return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

