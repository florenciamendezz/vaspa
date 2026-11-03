<?php
/* SE DEFINEN CONSTANTES PARA LOS CORREOS DE PRUEBA PARA EL ENVIO DE NOTIFICACION */
require_once __DIR__ . '/../Env.Class.php';
Env::cargar();

// MAIL DEL SISTEMA
if (!defined('MAIL_SISTEMA')) define('MAIL_SISTEMA', Env::get('MAIL_SISTEMA_USER', ''));
if (!defined('CONTRASENA_SISTEMA')) define('CONTRASENA_SISTEMA', Env::get('MAIL_SISTEMA_PASS', ''));

// MAIL DE SECRETARIA ACADEMICA
if (!defined('MAIL_SA')) define('MAIL_SA', Env::get('MAIL_SA_USER', ''));
if (!defined('CONTRASENA_SA')) define('CONTRASENA_SA', Env::get('MAIL_SA_PASS', ''));

// MAIL DEL DEPARTAMENTO DE CIENCIAS SOCIALES
if (!defined('MAIL_DEPTO_CS')) define('MAIL_DEPTO_CS', Env::get('MAIL_DEPTO_CS_USER', ''));
if (!defined('CONTRASENA_DEPTO_CS')) define('CONTRASENA_DEPTO_CS', Env::get('MAIL_DEPTO_CS_PASS', ''));

// MAIL DEL DEPARTAMENTO DE CIENCIAS NATURALES Y EXACTAS
if (!defined('MAIL_DEPTO_CNE')) define('MAIL_DEPTO_CNE', Env::get('MAIL_DEPTO_CNE_USER', ''));
if (!defined('CONTRASENA_DEPTO_CNE')) define('CONTRASENA_DEPTO_CNE', Env::get('MAIL_DEPTO_CNE_PASS', ''));

// MAIL DEL PROFESOR
if (!defined('MAIL_PROFESOR')) define('MAIL_PROFESOR', Env::get('MAIL_PROFESOR_USER', ''));
if (!defined('CONTRASENA_PROF')) define('CONTRASENA_PROF', Env::get('MAIL_PROFESOR_PASS', ''));

// MAIL DE ESCUELA
if (!defined('MAIL_ESCUELA')) define('MAIL_ESCUELA', Env::get('MAIL_ESCUELA_USER', ''));

// Define si se fuerza el envio real en desarrollo/localhost
$forzarEnvio = filter_var(Env::get('FORZAR_ENVIO_MAIL', false), FILTER_VALIDATE_BOOLEAN);
if (!defined('FORZAR_ENVIO_MAIL')) define('FORZAR_ENVIO_MAIL', $forzarEnvio);
