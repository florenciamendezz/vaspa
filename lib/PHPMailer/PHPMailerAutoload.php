<?php
/**
 * Adaptador de carga de PHPMailer (Composer PHPMailer 6.9+ con alias retrocompatible).
 * Mantiene compatibilidad total con las llamadas legacy `new PHPMailer` y `new SMTP`.
 */

$composerAutoload = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
    if (!class_exists('PHPMailer', false) && class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        class_alias(\PHPMailer\PHPMailer\PHPMailer::class, 'PHPMailer');
    }
    if (!class_exists('SMTP', false) && class_exists(\PHPMailer\PHPMailer\SMTP::class)) {
        class_alias(\PHPMailer\PHPMailer\SMTP::class, 'SMTP');
    }
    if (!class_exists('phpmailerException', false) && class_exists(\PHPMailer\PHPMailer\Exception::class)) {
        class_alias(\PHPMailer\PHPMailer\Exception::class, 'phpmailerException');
    }
    return;
}

/**
 * Fallback SPL autoloader local en caso de que vendor/ no esté presente.
 * @param string $classname The name of the class to load
 */
function PHPMailerAutoload($classname)
{
    $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'class.' . strtolower($classname) . '.php';
    if (is_readable($filename)) {
        require $filename;
    }
}

spl_autoload_register('PHPMailerAutoload', true, true);
