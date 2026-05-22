<?php
require_once __DIR__ . '/../PHPMailer/PHPMailerAutoload.php';
require_once __DIR__ . '/constantesMail.php';
require_once __DIR__ . '/../../modeloSistema/BDConexionSistema.Class.php';
require_once __DIR__ . '/../../modeloSistema/Asignatura.Class.php';
require_once __DIR__ . '/../../modeloSistema/Profesor.Class.php';

class notificacionCircuitoVaspa {

    /**
     * Envía un correo electrónico unificado utilizando la plantilla base
     */
    private static function enviarCorreo($destinatario, $asunto, $saludo, $introduccion, $cuerpo, $idAsignatura, $anio, $botonHTML = "") {
        // Cargar información de la asignatura para el bloque destacado
        $asignatura = new Asignatura($idAsignatura);
        $nombreAsignatura = $asignatura->getNombre();
        
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_SISTEMA;
        $mail->Password = CONTRASENA_SISTEMA;
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->FromName = "Sistema VASPA";
        $mail->addAddress($destinatario);
        
        // Cargar plantilla base
        $templatePath = __DIR__ . '/plantillaMail/mail_circuito_base.html';
        if (!file_exists($templatePath)) {
            error_log("Error: Plantilla de correo base no encontrada en: " . $templatePath);
            return false;
        }
        
        $message = file_get_contents($templatePath);
        
        // Reemplazar placeholders en la plantilla
        $message = str_replace('{{subject}}', htmlspecialchars($asunto), $message);
        $message = str_replace('{{saludo}}', htmlspecialchars($saludo), $message);
        $message = str_replace('{{introduccion}}', htmlspecialchars($introduccion), $message);
        $message = str_replace('{{codAsignatura}}', htmlspecialchars($idAsignatura), $message);
        $message = str_replace('{{nombreAsignatura}}', htmlspecialchars($nombreAsignatura), $message);
        $message = str_replace('{{anio}}', htmlspecialchars($anio), $message);
        $message = str_replace('{{cuerpo}}', htmlspecialchars($cuerpo), $message);
        $message = str_replace('{{botonHTML}}', $botonHTML, $message);
        
        $message = utf8_decode($message);
        
        $mail->isHTML(true);
        $mail->Subject = utf8_decode($asunto);
        $mail->msgHTML($message);
        
        if (!$mail->send()) {
            error_log("Error al enviar correo a {$destinatario}: " . $mail->ErrorInfo);
            return false;
        }
        return true;
    }

    /**
     * M11.1 - Profesor envía -> Notificar a Escuela (Rol 12, email dinámico)
     */
    public static function notificarEnvioAEscuela($idAsignatura, $anio) {
        $conexion = BDConexionSistema::getInstancia();
        // Obtener email dinámico del director de Escuela (rol 12)
        $sql = "SELECT u.email FROM usuario u 
                JOIN usuario_rol ur ON u.id = ur.id_usuario 
                WHERE ur.id_rol = 12 LIMIT 1";
        $res = $conexion->query($sql);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $emailEscuela = $row['email'];
            
            $asunto = "VASPA: Nuevo programa analítico pendiente de revisión";
            $saludo = "Estimado/a Director/a de Escuela,";
            $introduccion = "Le informamos que un Profesor ha subido y enviado a revisión el programa analítico de una asignatura perteneciente a su Escuela.";
            $cuerpo = "Por favor, ingrese al sistema para revisar el documento y adjuntar la versión firmada si corresponde.";
            $botonHTML = '<div class="button-container"><a href="' . Constantes::HOME_SISTEMA . '" class="btn">Ir a Bandeja de Revisión</a></div>';
            
            return self::enviarCorreo($emailEscuela, $asunto, $saludo, $introduccion, $cuerpo, $idAsignatura, $anio, $botonHTML);
        }
        return false;
    }

    /**
     * M11.2 - Escuela aprueba o Depto aprueba -> Notificar a VA (Rol 11)
     */
    public static function notificarEnvioAVA($idAsignatura, $anio, $origen = "Escuela") {
        $asunto = "VASPA: Programa analítico recibido para revisión de VA";
        $saludo = "Estimado/a Revisor/a de Vinculación Académica,";
        $introduccion = "El programa analítico ha avanzado en el circuito y ya se encuentra disponible en su bandeja de entrada.";
        $cuerpo = "El circuito avanzó desde el rol: {$origen}. Por favor, ingrese al sistema para acreditar el contenido o realizar la firma definitiva según corresponda.";
        $botonHTML = '<div class="button-container"><a href="' . Constantes::HOME_SISTEMA . '" class="btn">Ir al Sistema VASPA</a></div>';
        
        return self::enviarCorreo(MAIL_SA, $asunto, $saludo, $introduccion, $cuerpo, $idAsignatura, $anio, $botonHTML);
    }

    /**
     * M11.3 - VA aprueba -> Notificar a Departamento (Según depto de la materia)
     */
    public static function notificarEnvioADepto($idAsignatura, $anio) {
        $conexion = BDConexionSistema::getInstancia();
        $sql = "SELECT idDepartamento FROM asignatura WHERE id = '" . $conexion->real_escape_string($idAsignatura) . "'";
        $res = $conexion->query($sql);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $idDepto = $row['idDepartamento'];
            
            $emailDepto = "";
            $nombreDepto = "";
            if ($idDepto == '2') {
                $emailDepto = MAIL_DEPTO_CNE;
                $nombreDepto = "Ciencias Naturales y Exactas";
            } elseif ($idDepto == '1') {
                $emailDepto = MAIL_DEPTO_CS;
                $nombreDepto = "Ciencias Sociales";
            }
            
            if (!empty($emailDepto)) {
                $asunto = "VASPA: Programa analítico pendiente de revisión de nómina";
                $saludo = "Estimado/a Director/a del Departamento de {$nombreDepto},";
                $introduccion = "Vinculación Académica ha acreditado el programa analítico y lo ha derivado a su departamento para la revisión de la nómina docente.";
                $cuerpo = "Por favor, acceda al sistema para validar la nómina y firmar el documento.";
                $botonHTML = '<div class="button-container"><a href="' . Constantes::HOME_SISTEMA . '" class="btn">Revisar Programa</a></div>';
                
                return self::enviarCorreo($emailDepto, $asunto, $saludo, $introduccion, $cuerpo, $idAsignatura, $anio, $botonHTML);
            }
        }
        return false;
    }

    /**
     * M11.4 - VA firma final -> Notificar a Profesor (Aprobación final con link de descarga)
     */
    public static function notificarAprobacionFinal($idAsignatura, $anio, $emailDocente, $idProgramaPDF) {
        $asunto = "VASPA: ¡Su programa analítico ha sido aprobado definitivamente!";
        $saludo = "Estimado/a Profesor/a,";
        $introduccion = "Nos complace informarle que el circuito de revisión para su asignatura ha concluido exitosamente. El programa analítico ha recibido la aprobación definitiva de Vinculación Académica.";
        $cuerpo = "Puede descargar el PDF final firmado a través del siguiente botón o desde su panel de asignaturas en el sistema.";
        
        // Link de descarga
        $downloadUrl = "http://localhost/vaspa/vista/programa.descargarPDF.php?id=" . $idProgramaPDF;
        $botonHTML = '<div class="button-container"><a href="' . $downloadUrl . '" class="btn">Descargar Programa Aprobado (PDF)</a></div>';
        
        return self::enviarCorreo($emailDocente, $asunto, $saludo, $introduccion, $cuerpo, $idAsignatura, $anio, $botonHTML);
    }

    /**
     * M11.5 - Escuela o VA desaprueba -> Notificar a Profesor (Con comentario de desaprobación)
     */
    public static function notificarDesaprobacion($idAsignatura, $anio, $emailDocente, $comentario) {
        $asunto = "VASPA: Su programa analítico requiere correcciones";
        $saludo = "Estimado/a Profesor/a,";
        $introduccion = "Le informamos que se han registrado observaciones en el programa analítico presentado.";
        $cuerpo = "Motivo de la devolución:\n\"" . $comentario . "\"\n\nPor favor, realice las correcciones indicadas y vuelva a subir el archivo corregido a través del sistema.";
        $botonHTML = '<div class="button-container"><a href="' . Constantes::HOME_SISTEMA . '" class="btn">Ir a Mis Asignaturas</a></div>';
        
        return self::enviarCorreo($emailDocente, $asunto, $saludo, $introduccion, $cuerpo, $idAsignatura, $anio, $botonHTML);
    }

    /**
     * M11.6 - Depto desaprueba -> Notificar a VA como intermediario (Con comentario de Depto)
     */
    public static function notificarDesaprobacionDepto($idAsignatura, $anio, $comentario) {
        $asunto = "VASPA: Departamento ha devuelto un programa con observaciones";
        $saludo = "Estimado/a Revisor/a de Vinculación Académica,";
        $introduccion = "El Director del Departamento ha rechazado/desaprobado un programa analítico en revisión.";
        $cuerpo = "Observaciones del Departamento:\n\"" . $comentario . "\"\n\nPor favor, actúe como intermediario para comunicar estas observaciones al Profesor responsable fuera del sistema.";
        $botonHTML = '<div class="button-container"><a href="' . Constantes::HOME_SISTEMA . '" class="btn">Ver en Monitoreo</a></div>';
        
        return self::enviarCorreo(MAIL_SA, $asunto, $saludo, $introduccion, $cuerpo, $idAsignatura, $anio, $botonHTML);
    }

    /**
     * M11.7 - VA presiona "Enviar aviso" -> Revisor actual + Profesor
     */
    public static function notificarAvisoManual($idAsignatura, $anio, $emailDocente, $emailRevisor, $rolRevisor) {
        $asunto = "VASPA: Recordatorio de revisión de programa analítico pendiente";
        $saludo = "Estimado/a colega,";
        $introduccion = "Se ha enviado un recordatorio de retraso para el programa analítico indicado.";
        
        if (!empty($emailRevisor)) {
            // Notificar al Revisor
            $cuerpoRevisor = "El programa analítico de la asignatura se encuentra actualmente en su bandeja como: {$rolRevisor}. Agradecemos su revisión a la brevedad.";
            $botonHTML = '<div class="button-container"><a href="' . Constantes::HOME_SISTEMA . '" class="btn">Ingresar a VASPA</a></div>';
            self::enviarCorreo($emailRevisor, $asunto, $saludo, $introduccion, $cuerpoRevisor, $idAsignatura, $anio, $botonHTML);
        }
        
        if (!empty($emailDocente)) {
            // Notificar al Profesor
            $cuerpoProf = "Le recordamos que el programa analítico de su asignatura se encuentra en proceso de revisión en el sistema. Recibirá novedades cuando cambie de estado.";
            self::enviarCorreo($emailDocente, $asunto, $saludo, $introduccion, $cuerpoProf, $idAsignatura, $anio);
        }
        
        return true;
    }

    /**
     * M11.8 - Cron: Retraso automático (15 días) -> Solo a VA
     */
    public static function notificarAvisoAutomatico($programasRetrasados) {
        $destinatario = MAIL_SA;
        $asunto = "VASPA: Reporte diario de programas analíticos retrasados";
        
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_SISTEMA;
        $mail->Password = CONTRASENA_SISTEMA;
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->FromName = "Sistema VASPA";
        $mail->addAddress($destinatario);
        
        $cuerpo = "<p>Estimado/a Revisor/a de Vinculación Académica,</p>";
        $cuerpo .= "<p>El sistema ha detectado los siguientes programas analíticos activos en revisión sin movimientos durante los últimos 15 días:</p>";
        $cuerpo .= "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse; width:100%; font-family:Arial, sans-serif; font-size:14px;'>";
        $cuerpo .= "<tr style='background-color:#edf2f7; font-weight:bold;'><td>Código</td><td>Asignatura</td><td>Estado</td><td>Días inactivo</td></tr>";
        
        foreach ($programasRetrasados as $p) {
            $cuerpo .= "<tr><td>{$p['idAsignatura']}</td><td>{$p['nombreAsignatura']}</td><td>{$p['estado']}</td><td>{$p['dias']}</td></tr>";
        }
        
        $cuerpo .= "</table>";
        $cuerpo .= "<p>Por favor, ingrese al Panel de Monitoreo para tomar las acciones correspondientes (ej: enviar avisos manuales).</p>";
        $cuerpo .= "<div style='text-align:center; margin:25px 0;'><a href='http://localhost/vaspa/vista/monitoreo.circuito.php' style='background-color:#3182ce; color:#ffffff; padding:10px 20px; text-decoration:none; border-radius:5px; font-weight:bold;'>Ver Panel de Monitoreo</a></div>";
        
        $mail->isHTML(true);
        $mail->Subject = utf8_decode($asunto);
        $mail->Body = utf8_decode($cuerpo);
        
        if (!$mail->send()) {
            error_log("Error al enviar aviso automático de retraso a VA: " . $mail->ErrorInfo);
            return false;
        }
        return true;
    }

    /**
     * M11.9 - VA activa/desactiva vacancia -> Notificar a VA (Confirmación)
     */
    public static function notificarVacanciaCambio($vacanciaActiva) {
        $destinatario = MAIL_SA;
        $asunto = "VASPA: Cambio en el estado de Vacancia de Escuela";
        $saludo = "Estimado/a Revisor/a de Vinculación Académica,";
        
        $estado = ($vacanciaActiva == '1') ? "ACTIVADO" : "DESACTIVADO";
        $introduccion = "Se ha registrado un cambio en el modo Vacancia de Escuela del sistema.";
        
        $cuerpo = "El modo Vacancia de Escuela ha sido: <strong>{$estado}</strong> por un Administrador o miembro de Vinculación Académica.\n\n";
        if ($vacanciaActiva == '1') {
            $cuerpo .= "A partir de este momento, todos los nuevos programas analíticos subidos por profesores saltearán el paso de Escuela y pasarán directamente a su bandeja de Vinculación Académica. Los programas que se encontraban en bandeja de Escuela han sido redirigidos automáticamente.";
        } else {
            $cuerpo .= "El circuito vuelve a su flujo estándar de 5 pasos, requiriendo la firma de la Escuela correspondiente antes de llegar a Vinculación Académica.";
        }
        
        $botonHTML = '<div class="button-container"><a href="http://localhost/vaspa/vista/monitoreo.circuito.php" class="btn">Ver Panel de Monitoreo</a></div>';
        
        // Usamos una materia genérica o ficticia para cumplir la firma de enviarCorreo
        return self::enviarCorreo($destinatario, $asunto, $saludo, $introduccion, $cuerpo, "SISTEMA", date("Y"), $botonHTML);
    }

    /**
     * M11.10 - VA habilita re-presentación -> Notificar a Profesor
     */
    public static function notificarReentregaHabilitada($idAsignatura, $anio, $emailDocente) {
        $asunto = "VASPA: Se habilitó una nueva entrega excepcional para su asignatura";
        $saludo = "Estimado/a Profesor/a,";
        $introduccion = "Vinculación Académica ha habilitado una entrega excepcional para su programa analítico.";
        $cuerpo = "Se ha restablecido el registro del año actual. A partir de este momento, puede volver a subir y enviar a revisión un nuevo archivo PDF firmado o borrador para su asignatura.";
        $botonHTML = '<div class="button-container"><a href="' . Constantes::HOME_SISTEMA . '" class="btn">Acceder al Sistema</a></div>';
        
        return self::enviarCorreo($emailDocente, $asunto, $saludo, $introduccion, $cuerpo, $idAsignatura, $anio, $botonHTML);
    }
}
?>
