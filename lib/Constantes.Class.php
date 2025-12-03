<?php

setlocale(LC_TIME, 'es_AR.utf8');

/**
 * 
 * Clase para mantener las directivas de sistema.
 * Deben coincidir con las configuraciones del proyecto.
 * 
 * @author Eder dos Santos <esantos@uarg.unpa.edu.ar>
 * 
 */
class Constantes {

    
    const NOMBRE_SISTEMA = "Sistema VASPA";
    
    const WEBROOT = "/var/www/html/vaspa/";
    const APPDIR = "vaspa";
        
    const SERVER = "http://localhost";
    const APPURL = "http://localhost/vaspa";
    
    // constante que almacena la direccion de la pantalla principal del Sistema (Invitados)
    const HOMEURL = "http://localhost/vaspa/app/index.php";
    
    // constante que almacena la direccion de la pantalla principal del rol Administrador
    const HOMEAUTH = "http://localhost/vaspa/app/usuarios.php";
    
    // constante que almacena la direccion de la pantalla principal del rol Secretario Academico
    const HOME_VA = "http://localhost/vaspa/vista/panelVA.php";
    
    // constante que almacena la direccion de la pantalla principal del rol Profesor
    const HOME_PROF = "http://localhost/vaspa/vista/asignaturasDeProfesor.php";
    
    // constante que almacena la direccion de la pantalla principal del rol Departamento
    const HOME_DPTO = "http://localhost/vaspa/vista/revisar.programas.php";
    
    // constantes que almacenan el nombre de las BD que usa el Sistema
    const BD_SCHEMA = "bdgef_vaspa";
    const BD_USERS = "bdgef_vaspa";
    
}
