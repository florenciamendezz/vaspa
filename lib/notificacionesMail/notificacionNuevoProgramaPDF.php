<?php

require '../lib/PHPMailer/PHPMailerAutoload.php';
require '../lib/notificacionesMail/constantesMail.php';

function sendemailPDF($mail_username, $mail_userpassword, $mail_addAddress, $mail_subject, $template, $codAsignatura, $nombreAsignatura, $nombreProfesor, $rutaArchivo, $destinatario){
	$mail = new PHPMailer;
	$mail->isSMTP();                            
	$mail->Host = 'smtp.gmail.com';             
	$mail->SMTPAuth = true;                     
	$mail->Username = $mail_username;          
	$mail->Password = $mail_userpassword; 		
	$mail->SMTPSecure = 'tls';                  
	$mail->Port = 587;                          
    $mail->FromName = "Sistema VASPA";
	$mail->addAddress($mail_addAddress);   
	
    $message = file_get_contents($template);
    $message = str_replace('{{codAsignatura}}', $codAsignatura, $message);
    $message = str_replace('{{nombreAsignatura}}', $nombreAsignatura, $message);
    $message = str_replace('{{nombreProfesor}}', $nombreProfesor, $message);
    // Link to the file. Assuming the server is accessible via localhost/vaspa/CodigoFuente/archivos/programas/
    // Ideally this domain should be configurable.
    $link = "http://localhost/vaspa/CodigoFuente/archivos/programas/" . $rutaArchivo;
    $message = str_replace('{{link}}', $link, $message);
    $message = utf8_decode($message);
	
	$mail->isHTML(true);  
	
	$mail->Subject = $mail_subject;
    $mail->Subject = utf8_decode($mail->Subject); 
                
	$mail->msgHTML($message);
        
    if($destinatario == 0){
        $destinatario = "Vinculación Académica";
    } else {
            $asignatura = new Asignatura($codAsignatura);
            $departamento = new Departamento($asignatura->getIdDepartamento());
            $destinatario = "Departamento de ".$departamento->getNombre();
            
    }
	if(!$mail->send()) {
        echo '<div class="alert alert-danger" role="alert">Ha ocurrido un error al enviar la notificaci&oacute;n por correo a '.$destinatario.'.<b>('.$mail->ErrorInfo.')</b></div>';
	} 
    else {
        echo '<div class="alert alert-success" role="alert">Notificaci&oacute;n enviada con &eacute;xito a '.$destinatario.'.</div>';
	}
}

function enviarMailNuevoProgramaPDFSA($idPrograma) {
    include_once '../modeloSistema/Asignatura.Class.php';
    include_once '../modeloSistema/Profesor.Class.php';
    include_once '../modeloSistema/ProgramaPDFDetalle.Class.php';
    
    $programa = new ProgramaPDFDetalle($idPrograma);
    $asignatura = new Asignatura($programa->getIdAsignatura());
    $profesor = new Profesor($asignatura->getIdProfesor());
    $nombreProfesor = $profesor->getApellido().', '.$profesor->getNombre(); 
    $nombreAsignatura = $asignatura->getNombre();
    
    $mail_username = MAIL_SISTEMA; 
    $mail_userpassword = CONTRASENA_SISTEMA; 
    $mail_addAddress = MAIL_SA; 
    
    // We need a new template or reuse one. I'll create a new one inline or assume a path.
    // For now let's use a new template file.
    $template = "../lib/notificacionesMail/plantillaMail/mail_Nuevo_Programa_PDF.html"; 
    $mail_subject = "Nuevo Programa PDF de $nombreAsignatura para revisar";
    $destinatario = 0;
    sendemailPDF($mail_username, $mail_userpassword, $mail_addAddress, $mail_subject, $template, $asignatura->getId(), $nombreAsignatura, $nombreProfesor, $programa->getRutaArchivo(), $destinatario); 
}

function enviarMailNuevoProgramaPDFDepartamento($idPrograma) {
    include_once '../modeloSistema/Asignatura.Class.php';
    include_once '../modeloSistema/Profesor.Class.php';
    include_once '../modeloSistema/ProgramaPDFDetalle.Class.php';
    include_once '../modeloSistema/Departamento.Class.php';
    
    $programa = new ProgramaPDFDetalle($idPrograma);
    $asignatura = new Asignatura($programa->getIdAsignatura());
    $profesor = new Profesor($asignatura->getIdProfesor());
    $departamento = new Departamento($asignatura->getIdDepartamento());
    $nombreProfesor = $profesor->getApellido().', '.$profesor->getNombre(); 
    $nombreAsignatura = $asignatura->getNombre();
    
    $mail_username = MAIL_SISTEMA; 
    $mail_userpassword = CONTRASENA_SISTEMA; 
    if ($departamento->getNombre() == 'Ciencias Sociales'){
        $mail_addAddress = MAIL_DEPTO_CS; 
    } else {
        $mail_addAddress = MAIL_DEPTO_CNE; 
    }

    $template = "../lib/notificacionesMail/plantillaMail/mail_Nuevo_Programa_PDF.html"; 
    $mail_subject = "Nuevo Programa PDF de $nombreAsignatura para revisar";
    $destinatario = 1;
    sendemailPDF($mail_username, $mail_userpassword, $mail_addAddress, $mail_subject, $template, $asignatura->getId(), $nombreAsignatura, $nombreProfesor, $programa->getRutaArchivo(), $destinatario); 
}

function notificarNuevoProgramaPDF($idPrograma) {
    enviarMailNuevoProgramaPDFSA($idPrograma);
    enviarMailNuevoProgramaPDFDepartamento($idPrograma);
}
?>
