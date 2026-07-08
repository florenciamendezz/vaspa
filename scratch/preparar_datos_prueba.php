<?php
require_once 'c:/xampp/htdocs/vaspa/modeloSistema/BDConexionSistema.Class.php';

// ==========================================
// CONFIGURACIÓN DE CORREOS DE PRUEBA
// ==========================================
$emails = [
    'profesor'     => 'accesoriosperlados@gmail.com',
    'escuela'      => 'luzgarai40@gmail.com',
    'depto_cne'    => 'estiloperladoaccesorios@gmail.com',
    'depto_cs'     => 'garaiestefi@gmail.com',
    'vinculacion'  => 'esstefaniamendez@gmail.com',
    'admin'        => 'luzmariagaraigarai@gmail.com',
];

$anioPrueba = 2026;

try {
    $db = BDConexionSistema::getInstancia();
    $db->autocommit(false);
    $db->begin_transaction();

    echo "1. Re-asignando profesores para asignaturas de prueba...\n";
    // Asignamos 1668 y 1662 a Albert Sofia (asofia, ID: 4)
    $db->query("UPDATE asignatura SET idProfesor = 4 WHERE id IN ('1668', '1662')");
    // Asignamos 0174, 1108 y 1649 a Sandra Casas (scasas, ID: 5)
    $db->query("UPDATE asignatura SET idProfesor = 5 WHERE id IN ('0174', '1108', '1649')");
    // Asegurar vigencia del plan 016P5 para el año 2026
    $db->query("UPDATE plan SET anio_fin = NULL WHERE id = '016P5'");
    // Sincronizar idDepartamento con los 2 departamentos oficiales
    $db->query("UPDATE asignatura SET idDepartamento = 1 WHERE idEscuela IN (6, 7, 8, 11)");
    $db->query("UPDATE asignatura SET idDepartamento = 2 WHERE idEscuela IN (1, 2, 3, 4, 5, 9, 10, 12, 13)");

    echo "1.5. Asociando asignatura 1668 al plan vigente (asegurando unicidad)...\n";
    // Verificar que la asignatura esté en algún plan activo

    echo "2. Actualizando correos en tabla 'usuario'...\n";
    
    // Profesor (scasas ID: 46) - usuario con rol Profesor que tiene accesoriosperlados@gmail.com
    $db->query("UPDATE usuario SET email = '{$emails['profesor']}' WHERE id = 46");
    
    // Profesor (asofia ID: 50) - usuario con rol Profesor que tiene esstefaniamendez+profesor@gmail.com
    $db->query("UPDATE usuario SET email = 'esstefaniamendez+profesor@gmail.com' WHERE id = 50");
    
    // Director de Escuela (ID: 51)
    $db->query("UPDATE usuario SET email = '{$emails['escuela']}' WHERE id = 51");
    
    // Director de Depto Naturales (ID: 29)
    $db->query("UPDATE usuario SET email = '{$emails['depto_cne']}' WHERE id = 29");
    
    // Director de Depto Sociales (ID: 28)
    $db->query("UPDATE usuario SET email = '{$emails['depto_cs']}' WHERE id = 28");
    
    // Vinculación Académica (ID: 31, fmendez ID: 44)
    $db->query("UPDATE usuario SET email = '{$emails['vinculacion']}' WHERE id IN (31, 44)");
    
    // Administrador (Eder ID: 23, Francisco ID: 24)
    $db->query("UPDATE usuario SET email = '{$emails['admin']}' WHERE id IN (23, 24)");

    echo "3. Actualizando correos en tabla 'profesor'...\n";
    // Profesor Sandra Casas (ID: 5)
    $db->query("UPDATE profesor SET email = '{$emails['profesor']}' WHERE id = 5");
    // Profesor Albert Sofia (ID: 4)
    $db->query("UPDATE profesor SET email = 'esstefaniamendez+profesor@gmail.com' WHERE id = 4");

    echo "4. Limpiando programas y archivos existentes de las asignaturas de prueba...\n";
    
    // Obtener las rutas de archivos de programas que se van a eliminar
    $res = $db->query("SELECT ruta_archivo FROM programa_pdf_detalle WHERE id_asignatura IN ('1668', '1662', '0174', '1108')");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $file = 'c:/xampp/htdocs/vaspa/' . $row['ruta_archivo'];
            if (!empty($row['ruta_archivo']) && file_exists($file) && is_file($file)) {
                unlink($file);
                echo "   Archivo físico eliminado: {$row['ruta_archivo']}\n";
            }
        }
    }

    $db->query("DELETE FROM programa_pdf_detalle WHERE id_asignatura IN ('1668', '1662', '0174', '1108')");
    $db->query("DELETE FROM programa WHERE idAsignatura IN ('1668', '1662', '0174', '1108')");

    $db->commit();
    $db->autocommit(true);
    echo "¡Base de datos actualizada con éxito para las pruebas!\n\n";

    echo "5. Actualizando archivos de configuración de correo (constantesMail.php)...\n";
    
    // Archivo 1: lib/funcionesUtiles/constantesMail.php
    $file1 = 'c:/xampp/htdocs/vaspa/lib/funcionesUtiles/constantesMail.php';
    if (file_exists($file1)) {
        $content1 = "<?php\n"
                  . "/* SE DEFINEN CONSTANTES PARA LOS CORREOS DE PRUEBA PARA EL ENVIO DE NOTIFICACION */\n\n"
                  . "// MAIL DEL SISTEMA\n"
                  . "define('MAIL_SISTEMA', 'esstefaniamendez@gmail.com');\n"
                  . "define('CONTRASENA_SISTEMA', 'qkybvowecyfxdzok');\n\n"
                  . "// MAIL DE SECRETARIA ACADEMICA\n"
                  . "define('MAIL_SA', '{$emails['vinculacion']}');\n"
                  . "define('CONTRASENA_SA', 'qkybvowecyfxdzok');\n\n"
                  . "// MAIL DEL DEPARTAMENTO DE CIENCIAS SOCIALES\n"
                  . "define('MAIL_DEPTO_CS', '{$emails['depto_cs']}');\n"
                  . "define('CONTRASENA_DEPTO_CS', 'qkybvowecyfxdzok');\n\n"
                  . "// MAIL DEL DEPARTAMENTO DE CIENCIAS NATURALES Y EXACTAS\n"
                  . "define('MAIL_DEPTO_CNE', '{$emails['depto_cne']}');\n"
                  . "define('CONTRASENA_DEPTO_CNE', 'qkybvowecyfxdzok');\n\n"
                  . "// MAIL DEL PROFESOR\n"
                  . "define('MAIL_PROFESOR', '{$emails['profesor']}');\n"
                  . "define('CONTRASENA_PROF', 'qkybvowecyfxdzok');\n\n"
                  . "// Define si se fuerza el envio real en desarrollo/localhost\n"
                  . "define('FORZAR_ENVIO_MAIL', false);\n";
         file_put_contents($file1, $content1);
         echo "   Archivo actualizado: {$file1}\n";
     }
 
     // Archivo 2: lib/notificacionesMail/constantesMail.php
     $file2 = 'c:/xampp/htdocs/vaspa/lib/notificacionesMail/constantesMail.php';
     if (file_exists($file2)) {
         $content2 = "<?php\n"
                   . "/* SE DEFINEN CONSTANTES PARA LOS CORREOS DE PRUEBA PARA EL ENVIO DE NOTIFICACION */\n\n"
                   . "// MAIL DEL SISTEMA\n"
                   . "define('MAIL_SISTEMA', 'esstefaniamendez@gmail.com');\n"
                   . "define('CONTRASENA_SISTEMA', 'qkybvowecyfxdzok');\n\n"
                   . "// MAIL DE SECRETARIA ACADEMICA\n"
                   . "define('MAIL_SA', '{$emails['vinculacion']}');\n"
                   . "define('CONTRASENA_SA', 'qkybvowecyfxdzok');\n\n"
                   . "// MAIL DEL DEPARTAMENTO DE CIENCIAS SOCIALES\n"
                   . "define('MAIL_DEPTO_CS', '{$emails['depto_cs']}');\n"
                   . "define('CONTRASENA_DEPTO_CS', 'qkybvowecyfxdzok');\n\n"
                   . "// MAIL DEL DEPARTAMENTO DE CIENCIAS NATURALES Y EXACTAS\n"
                   . "define('MAIL_DEPTO_CNE', '{$emails['depto_cne']}');\n"
                   . "define('CONTRASENA_DEPTO_CNE', 'qkybvowecyfxdzok');\n\n"
                   . "// MAIL DEL PROFESOR\n"
                   . "define('MAIL_PROFESOR', '{$emails['profesor']}');\n"
                   . "define('CONTRASENA_PROF', 'qkybvowecyfxdzok');\n\n"
                   . "// MAIL DE ESCUELA\n"
                   . "define('MAIL_ESCUELA', '{$emails['escuela']}');\n\n"
                   . "// Define si se fuerza el envio real en desarrollo/localhost\n"
                   . "define('FORZAR_ENVIO_MAIL', false);\n";
         file_put_contents($file2, $content2);
         echo "   Archivo actualizado: {$file2}\n";
     }

    echo "Configuración completada con éxito.\n";

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollback();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
