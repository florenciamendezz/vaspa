<?php
// HABILITAR ERRORES EN PANTALLA (solo para desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// (opcional) loguear también a archivo
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xampp/php/logs/php_error_log');
include_once '../lib/ControlAcceso.Class.php'; ?>

<style>
    .dropdown {
        position: relative;
        /* display: inline-block;*/
    }

    .dropdown-content {
        display: none;
        position: absolute; 
        background-color: #343a40 !important;
        /*        min-width: 160px;
                box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
        */
        z-index: 1;
    }

    .dropdown-content a {
        color: black;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
    }

    .dropdown-content a:hover {background-color: #ddd;}

    .dropdown:hover .dropdown-content {display: block;}

    .dropdown:hover .dropbtn {background-color: #666666;}
</style>


<!-- Los estilos de navbar son definidos en la libreria css de Bootstrap -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    
    <?php if (ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_VER_VIGENCIA_PROGRAMAS)) { ?>
        <a class="navbar-brand" href="../vista/panelVA.php">
            <img src="../lib/img/VASPA_isotipo.png" width="40" height="30" class="d-inline-block align-top" alt="">
            VASPA
        </a>
    <?php } else {
        ?>
        <a class="navbar-brand" href="#">
            <img src="../lib/img/VASPA_isotipo.png" width="40" height="30" class="d-inline-block align-top" alt="">
            VASPA
        </a> 
    <?php } ?>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="toggle navigation">
        <span class="navbar-toggler-icon"></span>   
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mr-auto">
            <?php if (ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_USUARIOS)) { ?>
                <div class="dropdown">
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <span class="oi oi-person" />
                            Adm. Usuarios
                        </a>
                    </li>
                    <div class="dropdown-content">
                        <a class="nav-link" href="../app/usuarios.php">
                            <span class="oi oi-person" />
                            Usuarios
                        </a>

                        <?php if (ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_ROLES)) { ?>
                            <a class = "nav-link" href = "../app/roles.php">
                                <span class = "oi oi-graph" />
                                Roles
                            </a>
                        <?php } ?>
                        <?php if (ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_PERMISOS)) { ?>
                            <a class="nav-link" href="../app/permisos.php">
                                <span class="oi oi-lock-locked" />
                                Permisos
                            </a>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>

            <!--                MENU "GESTIONAR PROGRAMAS"             -->
            <?php if (ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_REVISAR_PROGRAMA) || ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_GENERAR_INFORME_GERENCIAL)) { ?>
                <div class="dropdown">
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <span class="oi oi-document" />
                            Gestionar Programas
                        </a>
                    </li>
                    <div class="dropdown-content">

                        <?php if (ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_REVISAR_PROGRAMA)) { ?>
                            <a class = "nav-link" href = "../vista/revisar.programas.php">
                                <span class = "oi oi-document" />
                                Revisar Programa
                            </a>
                        <?php } ?>

                        <?php if (ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_GENERAR_INFORME_GERENCIAL)) { ?>
                            <a class = "nav-link" href = "../vista/informeGerencial.programas.php">
                                <span class = "oi oi-bar-chart" />
                                Informe Gerencial de Programas
                            </a>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>





            
            <?php if (ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_CARRERAS)) { ?>
                <li class="nav-item">
                    <a class="nav-link" href="../vista/carreras.php">
                        <span class="oi oi-spreadsheet" />
                        Carreras
                    </a>
                </li>                
            <?php } ?>

            


            
            <?php 
            
            $UsuarioSes = $_SESSION['usuario'];
            $perfil = $UsuarioSes->roles[0]->nombre;
            
            if (ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_PROFESORES && $perfil == PermisosSistema::ROL_VINCULACION_ACADEMICA)) { ?>
                <li class="nav-item">
                    <a class="nav-link" href="../vista/profesores.php">
                        <span class="oi oi-people" />
                        Profesores
                    </a>
                </li>                
            <?php } ?>

            




            <li class="nav-item">
                <a class="nav-link" href="../app/salir.php">
                    <span class="oi oi-account-logout" /> 
                    Salir
                </a>
            </li>
        </ul>

    </div>

</nav>


<div class="alert alert-info alert-dismissible fade show" role="alert">
    Ud. est&aacute; conectad@ como <strong><?= $_SESSION['usuario']->nombre; ?></strong>.
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
