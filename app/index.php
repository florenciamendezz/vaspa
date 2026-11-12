<?php
include_once __DIR__ . '/../lib/ControlAcceso.Class.php'; 
?>

<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="">
        <meta name="author" content="">

        <script type="text/javascript" src="../lib/JQuery/jquery-3.3.1.js"></script>
        <script src="../lib/popper/popper.min.js"></script>
        <script type="text/javascript" src="../lib/bootstrap-4.1.1-dist/js/bootstrap.min.js"></script>
        <link rel="stylesheet" href="../lib/bootstrap-4.1.1-dist/css/bootstrap.css" />
        <link href="../lib/bootstrap-select/bootstrap-select.min.css" rel="stylesheet"/>
        <script src="../lib/bootstrap-select/bootstrap-select.min.js"></script>
        <link rel="stylesheet" href="../lib/open-iconic-master/font/css/open-iconic-bootstrap.css" />
        <link href="../lib/bootstrap-4.1.1-dist/css/uargflow_footer.css" type="text/css" rel="stylesheet" />
        <meta name="google-signin-client_id" content="356408280239-7airslbg59lt2nped9l4dtqm2rf25aii.apps.googleusercontent.com" />
        <script type="text/javascript" src="https://apis.google.com/js/platform.js" async defer></script>
        <script type="text/javascript" src="../lib/login.js"></script>
        <script type="text/javascript" src="../lib/quicksearch/jquery.quicksearch.js"></script>

        <title><?php echo Constantes::NOMBRE_SISTEMA; ?> - Bienvenida</title>
        
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../lib/css/premium.css" />
        <style>
            .card-welcome {
                background: #ffffff;
                border: 1px solid #dcdcdc;
                border-radius: 10px;
                box-shadow: 0 6px 22px rgba(0, 0, 0, 0.09);
                font-family: 'Roboto', sans-serif;
                margin-top: 1.5rem;
                margin-bottom: 2rem;
            }
            .card-welcome-header {
                background: linear-gradient(135deg, #1d262d 0%, #263238 100%);
                color: #ffffff;
                border-bottom: 3px solid #9ab300;
                border-top-left-radius: 9px;
                border-top-right-radius: 9px;
                padding: 1.5rem 2rem;
            }
            .navbar-premium {
                background-color: #1d262d !important;
                font-family: 'Roboto', sans-serif;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                border-bottom: 4px solid #9ab300 !important;
                padding: 0.65rem 1.5rem;
            }
            .navbar-premium .container {
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
            }
            .navbar-premium .navbar-brand {
                font-weight: 700;
                font-size: 1.35rem;
                color: #ffffff !important;
                display: inline-flex;
                align-items: center;
                gap: 0.65rem;
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
            }
            .navbar-premium .brand-text-wrap {
                display: flex;
                flex-direction: column;
                line-height: 1.15;
            }
            .navbar-premium .brand-sub {
                font-size: 0.72rem;
                font-weight: 400;
                color: #9ba6ae;
            }
            .footer-premium {
                background: #1d262d !important;
                font-family: 'Roboto', sans-serif;
                border-top: 4px solid #9ab300 !important;
                box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.15);
                color: #e2e8f0 !important;
                height: 58px;
                line-height: 54px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                font-size: 0.95rem;
                font-weight: 500;
            }
        </style>
    </head>
    <body>
        
        <nav class="navbar navbar-expand-lg navbar-dark navbar-premium">
            <div class="container align-items-center justify-content-between">
                <a class="navbar-brand" href="#">
                    <img src="../lib/img/VASPA_isotipo.png" width="40" height="30" class="d-inline-block align-top" alt="">
                    <span class="brand-text-wrap">
                        <span><?php echo Constantes::NOMBRE_SISTEMA; ?> UARG</span>
                        <span class="brand-sub">SIT UNPA-UARG</span>
                    </span>
                </a>
            </div>
        </nav>
               
        <div class="container py-2">
            <div class="card card-welcome">
                <div class="card-welcome-header">
                    <h3 class="mb-0 font-weight-bold"><?php echo Constantes::NOMBRE_SISTEMA; ?> - Bienvenida</h3>
                </div>

                <div class="card-body p-4">
                    <?php
                            if (!empty($_SESSION['auth_error']) || (isset($_SESSION['usuario']) && $_SESSION['usuario'] == "")) {

                                echo '<p><div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                                        <span class="oi oi-warning"></span> Usuario No Autenticado.
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                          <span aria-hidden="true">&times;</span>
                                        </button>
                                      </div></p>';
                                unset($_SESSION['auth_error']);
                                unset($_SESSION['usuario']);
                                session_destroy();
                            }
                    ?>
                    <p style="font-size: 1.02rem;">Estimado usuario: Bienvenido al <b>Sistema</b> para la <b>V</b>isualizaci&oacute;n,
                        <b>A</b>dministraci&oacute;n y <b>S</b>eguimiento de <b>P</b>rogramas de 
                        <b>A</b>signaturas <b>(VASPA)</b>,
                        una aplicaci&oacute;n desarrollada en la UARG - UNPA.</p>  
                    <hr>

                    <div class="row">
                        <div class="col-md-12 mb-4">                                
                            <form action="visualizar.programa.listar.php" method="post"> 
                                <div class="card card-premium mt-2 mb-2">
                                    
                                    <div class="card-header card-header-premium">
                                        <h5 class="mb-0"><span class="oi oi-magnifying-glass mr-2"></span> Visualizar Programa de Asignatura</h5>
                                        <p class="mb-0 mt-2 text-white" style="opacity: 0.9;">
                                            Seleccione A&ntilde;o y Carrera.
                                        </p>
                                    </div>
                                    
                                    <div class="card-body text-dark">
                                        <div class="form-group">
                                            <label for="selectAnio" class="font-weight-bold">A&ntilde;o</label>
                                            <select class="selectpicker show-tick form-control" data-live-search="true" data-width="100%" name="anio" id="selectAnio" title="Seleccione un a&ntilde;o" required="" data-size="7" data-container="body">
                                                <?php for ($i = date('Y'); $i >= 2011; $i--) { ?>
                                                    <option value="<?= $i; ?>"><?= $i; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="selectCarrera" class="font-weight-bold">Carrera</label>
                                            <select class="selectpicker show-tick form-control" data-live-search="true" data-width="100%" name="idCarrera" id="selectCarrera" title="Seleccione una carrera" required="" data-size="7" data-container="body">
                                            </select>
                                        </div>
                                        <div id="programasAsignaturas"></div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <div class="card card-premium mt-0 mb-2">
                                <div class="card-header card-header-premium">
                                    <h5 class="card-title mb-0"><span class="oi oi-account-login mr-2"></span> Ingreso al Sistema</h5>
                                </div>
                                <div class="card-body text-dark">
                                    <p class="card-text">Si usted es un Profesor, empleado de Vinculaci&oacute;n Acad&eacute;mica o Director de Departamento y desea realizar operaciones en el Sistema, por favor presione el siguiente bot&oacute;n.</p>
                                    <div class="mt-3">
                                        <div id="okgoogle" class="g-signin2" onclick="ClickLogin()" data-onsuccess="onSignIn" title="Acceder al <?= Constantes::NOMBRE_SISTEMA; ?>"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- 
                ========================================================================
                [INICIO: ACCESO RÁPIDO PARA PRUEBAS Y DESARROLLO]
                NOTA: Esta sección de código solo se renderiza si el entorno es local/testing
                ========================================================================
                -->
                <?php if (ControlAcceso::esEntornoPruebas()): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center" style="cursor: pointer;" data-toggle="collapse" data-target="#collapseLoginPruebas" aria-expanded="false" aria-controls="collapseLoginPruebas">
                                <h5 class="mb-0 font-weight-bold"><span class="oi oi-shield mr-2"></span> Acceso Rápido para Pruebas (Desarrollo)</h5>
                                <span class="badge badge-dark">Hacer clic para expandir / colapsar</span>
                            </div>
                            <div id="collapseLoginPruebas" class="collapse">
                                <div class="card-body">
                                    <p class="text-muted" style="font-size: 0.9rem;">
                                        <strong>[ENTORNO DE PRUEBAS]</strong> Utilice estos accesos rápidos para simular inicios de sesión sin necesidad del login de Google.
                                    </p>
                                    <form id="formLoginPruebas" method="POST" action="index.php">
                                        <input type="hidden" name="email" id="emailPrueba">
                                        <input type="hidden" name="nombre" id="nombrePrueba">
                                    </form>
                                    <script>
                                        function iniciarSesionPrueba(email, nombre) {
                                            document.getElementById('emailPrueba').value = email;
                                            document.getElementById('nombrePrueba').value = nombre;
                                            document.getElementById('formLoginPruebas').submit();
                                        }
                                    </script>
                                    <div class="list-group">
                                        <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" onclick="iniciarSesionPrueba('accesoriosperlados@gmail.com', 'Profesor Sandra Casas')">
                                             <span><strong>Profesor:</strong> Sandra Casas</span>
                                            <span class="badge badge-success px-2 py-1">accesoriosperlados@gmail.com</span>
                                        </button>
                                        <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" onclick="iniciarSesionPrueba('esstefaniamendez+profesor@gmail.com', 'Profesor Albert Sofia')">
                                            <span><strong>Profesor:</strong> Albert Sofia</span>
                                            <span class="badge badge-success px-2 py-1">esstefaniamendez+profesor@gmail.com</span>
                                        </button>
                                        <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" onclick="iniciarSesionPrueba('luzgarai40@gmail.com', 'Director de Escuela')">
                                            <span><strong>Director de Escuela</strong></span>
                                            <span class="badge badge-info px-2 py-1">luzgarai40@gmail.com</span>
                                        </button>
                                        <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" onclick="iniciarSesionPrueba('estiloperladoaccesorios@gmail.com', 'Director Depto Cs Naturales y Exactas')">
                                            <span><strong>Director Depto. Ciencias Naturales y Exactas</strong></span>
                                            <span class="badge badge-warning px-2 py-1">estiloperladoaccesorios@gmail.com</span>
                                        </button>
                                        <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" onclick="iniciarSesionPrueba('garaiestefi@gmail.com', 'Director Depto Cs Sociales')">
                                            <span><strong>Director Depto. Ciencias Sociales</strong></span>
                                            <span class="badge badge-warning px-2 py-1">garaiestefi@gmail.com</span>
                                        </button>
                                        <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" onclick="iniciarSesionPrueba('esstefaniamendez@gmail.com', 'Vinculación Académica')">
                                            <span><strong>Vinculación Académica</strong></span>
                                            <span class="badge badge-primary px-2 py-1">esstefaniamendez@gmail.com</span>
                                        </button>
                                        <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" onclick="iniciarSesionPrueba('luzmariagaraigarai@gmail.com', 'Administrador')">
                                            <span><strong>Administrador</strong></span>
                                            <span class="badge badge-danger px-2 py-1">luzmariagaraigarai@gmail.com</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <!-- 
                ========================================================================
                [FIN: ACCESO RÁPIDO PARA PRUEBAS Y DESARROLLO]
                ========================================================================
                -->

            </div>
        </div>
    </div>
        
        <footer class="footer footer-premium">
            <?php echo Constantes::NOMBRE_SISTEMA; ?> 
            <img src="../lib/img/VASPA_isotipo.png" width="25" height="20" alt="">
            &copy; Servicio de Inform&aacute;tica y Telecomunicaciones (SIT) - UNPA-UARG
        </footer>
        
        <script type="text/javascript">$('.selectpicker').selectpicker({
            noneResultsText: 'No se encontraron resultados',
            container: 'body'
        });
        </script>
                
        <script>
            $(document).ready(function(){
                $(document).on('show.bs.modal', '.modal', function () {
                    $(this).appendTo('body');
                });

                $('#selectAnio').change(function () {
                    var anio = $('#selectAnio').val();
                    $("#programasAsignaturas").html('');
                    if (!anio) {
                        $('#selectCarrera').html('').selectpicker('refresh');
                        return;
                    }
                    $.ajax({
                      type: 'POST',
                      url: '../lib/consultaAjax/visualizar.programa.cargar.carreras.php',
                      data: {'anio': anio}
                    })
                    .done(function(carreras){
                      $('#selectCarrera').html(carreras).selectpicker('refresh');
                    })
                    .fail(function(){
                      alert('Hubo un error al cargar las carreras')
                    });
                });
                  
                $('#selectCarrera').change(function () {
                    var anio = $('#selectAnio').val();
                    var idCarrera = $('#selectCarrera').val();
                    
                    if (!idCarrera || idCarrera === '' || !anio || anio === '') {
                        $("#programasAsignaturas").html('');
                        return;
                    }

                    $.ajax({
                      type: 'POST',
                      url: '../lib/consultaAjax/visualizarPrograma/tablaProgramasAsignaturas.php',
                      data: {'anio': anio,
                            'idCarrera': idCarrera}
                    })
                    .done(function(programas){
                      $("#programasAsignaturas").html(programas);
                    })
                    .fail(function(){
                      alert('Hubo un error al cargar los programas de asignaturas.')
                    });
                });
            });
        </script>
    </body>
</html>
