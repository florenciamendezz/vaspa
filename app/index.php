<?php
// HABILITAR ERRORES EN PANTALLA (solo para desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);



// (opcional) loguear también a archivo
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xampp/php/logs/php_error_log');

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
    </head>
    <body>
        
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container navbar-dark bg-dark align-items-start">
                <a class="navbar-brand" href="#">
                    <img src="../lib/img/VASPA_isotipo.png" width="40" height="35" class="d-inline-block align-top" alt="">
                    &nbsp;&nbsp;<?php echo Constantes::NOMBRE_SISTEMA; ?>
                </a>
            </div>
        </nav>
               
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h3><?php echo Constantes::NOMBRE_SISTEMA; ?> - Bienvenida</h3>
                </div>

                <div class="card-body">
                    <?php
                            // VERIFICAMOS SI ESTA SETEADO EL USUARIO EN LA SESION Y SI ESTA VACIO (CADENA VACIA) PARA MOSTRAR EL MENSAJE QUE NO ES UN USUARIO REGISTRADO EN EL SISTEMA
                            if (isset($_SESSION['usuario']) && $_SESSION['usuario'] == "") {

                                echo '<p><div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                                        <span class="oi oi-warning"></span> Usuario No Autenticado.
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                          <span aria-hidden="true">&times;</span>
                                        </button>
                                      </div></p>';
                                session_destroy(); // destruimos la session para evitar que vuelva a mostrar el alert (de todos modos al actualizar la pagina le pide reenvio de formulario)
                            }
                    ?>
                    <p>Estimado usuario: Bienvenido al <b>Sistema</b> para la <b>V</b>isualizaci&oacute;n
                        <b>A</b>dministraci&oacute;n y <b>S</b>eguimiento de <b>P</b>rogramas de 
                        <b>A</b>signaturas <b>(VASPA)</b>,
                        una aplicaci&oacute;n desarrollada en la UARG - UNPA.</p>  
                    <hr>

                    <div class="row">
                        <div class="col-md-12 mb-1">                                
                            <form action="visualizar.programa.listar.php" method="post"> 
                                <div class="card">
                                    
                                    <div class="card-header">
                                        <h5>Visualizar Programa de Asignatura</h5>
                                        <p>
                                            Seleccione A&ntilde;o y Carrera.
                                        </p>
                                    </div>
                                    
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="selectAnio">A&ntilde;o</label>
                                            <br>
                                            <select class="selectpicker show-tick" data-live-search="true" data-width="100%" name="anio" id="selectAnio" title="Seleccione un a&ntilde;o" required="" data-size="7">
                                                <?php for ($i = date('Y'); $i >= 2011; $i--) { ?>
                                                    <option value="<?= $i; ?>"><?= $i; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="selectCarrera">Carrera</label>
                                            <br>
                                            <select class="selectpicker show-tick" data-live-search="true" data-width="100%" name="idCarrera" id="selectCarrera" title="Seleccione una carrera" required="" data-size="7">
                                            </select>
                                        </div>
                                        <div id="programasAsignaturas"></div>
                                    </div>
                                    
<!--                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-outline-success">
                                            <span class="oi oi-check"></span> Confirmar
                                        </button>
                                    </div>-->
                                    </div>
                            </form>
                    </div>
                        

                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Ingreso al Sistema</h5>
                                <p class="card-text">Si usted es un Profesor, empleado de Vinculaci&oacute;n Acad&eacute;mica o Director de Departamento y desea realizar operaciones en el Sistema, por favor presione el siguiente bot&oacute;n.</p>
                                <div id="okgoogle" class="g-signin2" onclick="ClickLogin()" data-onsuccess="onSignIn" title="Acceder al <?= Constantes::NOMBRE_SISTEMA; ?>"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 
                ========================================================================
                [INICIO: ACCESO RÁPIDO PARA PRUEBAS Y DESARROLLO]
                NOTA PARA EL FUTURO: Esta sección de código (tarjeta y script) debe ser
                eliminada por completo antes del despliegue a producción.
                ========================================================================
                -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center" style="cursor: pointer;" data-toggle="collapse" data-target="#collapseLoginPruebas" aria-expanded="false" aria-controls="collapseLoginPruebas">
                                <h5 class="mb-0 font-weight-bold"><span class="oi oi-shield mr-2"></span> Acceso Rápido para Pruebas (Desarrollo)</h5>
                                <span class="badge badge-dark">Hacer clic para expandir / colapsar</span>
                            </div>
                            <div id="collapseLoginPruebas" class="collapse">
                                <div class="card-body">
                                    <p class="text-muted" style="font-size: 0.85rem;">
                                        <strong>[ENTORNO DE PRUEBAS]</strong> Utilice estos accesos rápidos para simular inicios de sesión sin necesidad del login de Google.
                                    </p>
                                    <form id="formLoginPruebas" method="POST" action="index.php">
                                        <input type="hidden" name="email" id="emailPrueba">
                                        <input type="hidden" name="nombre" id="nombrePrueba">
                                    </form>
                                    <script>
                                        // Función de autenticación simulada de prueba (Eliminar en producción)
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
                <!-- 
                ========================================================================
                [FIN: ACCESO RÁPIDO PARA PRUEBAS Y DESARROLLO]
                ========================================================================
                -->

            </div>
        </div>
    </div>
        
         <footer class="footer">
            <?php echo Constantes::NOMBRE_SISTEMA; ?> 
            <img src="../lib/img/VASPA_isotipo.png" width="25" height="20"  alt="">
             UNPA-UARG
        </footer>
        
        <script type="text/javascript">$('.selectpicker').selectpicker({
            noneResultsText: 'No se encontraron resultados'});
        </script>
                
        <script>
            $(document).ready(function(){
                // actualiza la lista carreras
                  $('#selectAnio').change(function () {
                    var anio = $('#selectAnio').val();
                    //alert(anio);
                    $.ajax({
                      type: 'POST',
                      url: '../lib/consultaAjax/visualizar.programa.cargar.carreras.php',
                      data: {'anio': anio}
                    })
                    .done(function(carreras){
                      $(".selectpicker").selectpicker(); 
                      $('#selectCarrera').html(carreras).selectpicker('refresh');
                    })
                    .fail(function(){
                      alert('Hubo un error al cargar las asignaturas')
                    });
                  });
                  
                  // Envia por ajax el año y la carrera para luego obtener una tabla con los programas de asignaturas para el año y carrera seleccionados
                  $('#selectCarrera').change(function () {
                    // Recuperamos el anio y la carrera
                    var anio = $('#selectAnio').val();
                    var idCarrera = $('#selectCarrera').val();
                    //alert(anio+" "+idCarrera);
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
