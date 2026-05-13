<?php
header ('Content-Type: text/html; charset=ISO-8859-1');
/* Aqui comienza el CU Revisar Programa
 * Observaciones: 
 * - Rol Vinculación Académica y Administrador comparten la misma funcionalidad
 * Esto quiere decir que si el usuario tiene el rol de Admin va a revisar los programas
 * como si fuese un usuario de VA. (preguntar a los chicos)
 * */
include_once '../lib/ControlAcceso.Class.php';
ControlAcceso::requierePermiso(PermisosSistema::PERMISO_REVISAR_PROGRAMA);
include_once '../modeloSistema/BDConexionSistema.Class.php';
require_once '../controlSistema/ManejadorCarrera.php';
include_once '../lib/funcionesUtiles/constantesMail.php'; // Incluido aquí para asegurar disponibilidad

$manejadorCarrera = new ManejadorCarrera();
$carreras = $manejadorCarrera->getColeccion();

// Obtenemos el rol del usuario logueado en el sistema
$Usuario = $_SESSION['usuario'];
$rol = $Usuario->roles[0]->nombre;

$filtro = '1=1';// Filtro por defecto

// --- INICIO: LÓGICA DE FILTRADO DINÁMICO PARA CARGA INICIAL ---
// La clave es que el filtro debe excluir lo que el rol ya aprobó (aprobadoX = 1).
if ($rol == PermisosSistema::ROL_ADMIN || $rol == PermisosSistema::ROL_VINCULACION_ACADEMICA){
    $rol = 'VA'; // administrador / VA
    // Filtro VA: Sólo si NO ha sido aprobado por VA
    $filtro = " (aprobadoVa IS NULL OR aprobadoVa = 0) "; 
} elseif ($rol == PermisosSistema::ROL_DIRECTOR_DEPARTAMENTO) {
    if ($Usuario->email == MAIL_DEPTO_CNE){
        $rol = 'DCNE'; // Dpto Ciencias Naturales y Exactas;
        // Filtro Depto: Sólo si NO ha sido aprobado por Depto
        $filtro = " idDepartamento = '2' AND (aprobadoDepto IS NULL OR aprobadoDepto = 0) "; 
    } elseif ($Usuario->email == MAIL_DEPTO_CS) {
        $rol = 'DCS'; // Dpto Ciencias Sociales 
        // Filtro Depto: Sólo si NO ha sido aprobado por Depto
        $filtro = " idDepartamento = '1' AND (aprobadoDepto IS NULL OR aprobadoDepto = 0) "; 
    } else {
        $rol = PermisosSistema::ROL_DIRECTOR_DEPARTAMENTO; // Mantener el rol original para la lógica de la vista
        $filtro = " (aprobadoVa IS NULL OR aprobadoVa = 0) AND (aprobadoDepto IS NULL OR aprobadoDepto = 0) ";
    } 
} elseif ($rol == PermisosSistema::ROL_DIRECTOR_ESCUELA) {
    // Filtro Escuela: Sólo si NO ha sido aprobado por Escuela
    $filtro = " (aprobadoEscuela IS NULL OR aprobadoEscuela = 0) ";
}
$anioActual = date("Y"); //obtenemos el anio (4 digitos) del servidor (anio actual)

$query = "SELECT DISTINCT (p.id) as idPrograma, nombre, a.id, p.anio, p.vigencia, p.fechaCarga, ppd.id as idProgramaPDF 
                 FROM plan pl
                 JOIN plan_asignatura pa 
                 ON pl.id = pa.idPlan
                 JOIN asignatura a 
                 ON pa.idAsignatura = a.id 
                 JOIN programa p 
                 ON a.id = p.idAsignatura 
                 LEFT JOIN programa_pdf_detalle ppd
                 ON p.idAsignatura = ppd.id_asignatura AND p.anio = ppd.anio
                 WHERE enRevision = 1 AND (fueDesaprobado IS NULL OR fueDesaprobado = 0) AND $filtro " // Se incluye el filtro de desaprobado y el filtro por rol
                 . "AND p.anio <= {$anioActual} "
                 . "AND (p.anio+p.vigencia-1) >= {$anioActual} "
                 . "ORDER BY p.fechaCarga DESC "
                 . "LIMIT 20";

// Ejecutamos la query

function getVigencia($anio, $vigencia) {
    switch ($vigencia) {
        case 1:
            return $anio;
        case 2:
            return $anio.' - '.($anio+1);
        case 3:
            return $anio.' - '.($anio+1).' - '.($anio+2);
        default:
            return $anio;
    }
}

function obtenerProgramasAsignaturasRecientes($query) {
    $resultado = BDConexionSistema::getInstancia()->query($query);
    $html = '<h5 class="text-center text-muted">Programas recientes No Revisados</h5>';
    if ($resultado !== false){
        if ($resultado->num_rows > 0) {
            // Creamos la tabla donde presentaremos la info
            $html .= '<table class="table table-hover table-sm" id="tablaPrograma">
                        <thead>
                            <tr class="table-info">
                                <th>Programa de</th>
                                <th>C&oacute;digo</th>
                                <th>Vigencia</th>
                                <th>Fecha de Carga</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>';
            for ($x = 0; $x < $resultado->num_rows; $x++) {
                
                $fila = $resultado->fetch_assoc();
                $fechaCarga = new DateTime($fila['fechaCarga']);
                $fechaCarga = $fechaCarga->format('d/m/y');
                $html .= '<tr>';
                $html .= '<td>'.$fila['nombre'].'</td>';
                $html .= '<td>'.$fila['id'].'</td>';
                $html .= '<td>'.getVigencia($fila['anio'], $fila['vigencia']).'</td>';
                $html .= '<td>'.$fechaCarga.'</td>';
                
                // Determinamos el enlace corrector (PDF vs Legacy)
                if (!empty($fila['idProgramaPDF'])) {
                     $link = "revisar.programa.pdf.php?id=".$fila['idProgramaPDF'];
                } else {
                     $link = "revisar.programa.php?id=".$fila['idPrograma'];
                }

                $html .= '<td><a title="Revisar Programa" href="'.$link.'">
                                         <button type="button" class="btn btn-outline-success">
                                             <span class="oi oi-document"></span>
                                         </button></a></td>';
                $html .= '</tr>';

            }
            // cerramos etiquetas de la tabla
            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '<div class="alert alert-info">Mostrando los '.min(20, $resultado->num_rows).' programas más recientes.</div>';
        } else { // No hay registros --> Mostramos mensaje 
            $html .= '<div class="alert alert-warning alert-dismissible fade show text-center" role="alert">
                      No hay programas de asignaturas para revisar.
                      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                      </button>
                    </div>';
        }
    } else { // Ocurrio un error al realizar peticion --> Mostramos mensaje
            $html .= '<div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                      Ocurrio un Error al realizar peticion a la BD.
                      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                      </button>
                    </div>';
        }
    return $html;
}
?>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <script type="text/javascript" src="../lib/JQuery/jquery-3.3.1.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
        <script type="text/javascript" src="../lib/bootstrap-4.1.1-dist/js/bootstrap.min.js"></script>
        <link rel="stylesheet" href="../lib/bootstrap-4.1.1-dist/css/bootstrap.css" />
        <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.8/css/bootstrap-select.min.css" rel="stylesheet"/>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.8/js/bootstrap-select.min.js"></script>
        <link rel="stylesheet" href="../lib/datatable/dataTables.bootstrap4.min.css" />
        <script type="text/javascript" src="../lib/datatable/jquery.dataTables.min.js"></script>
        <script type="text/javascript" src="../lib/datatable/dataTables.bootstrap4.min.js"></script>
        <link rel="stylesheet" href="../lib/open-iconic-master/font/css/open-iconic-bootstrap.css" />
           <title><?php echo Constantes::NOMBRE_SISTEMA; ?> - Revisar Programas</title>
    </head>
    <body>

        <?php include_once '../gui/navbar.php'; ?>

        <div class="container">

            <div class="card">
                <div class="card-header">
                    <h3>Revisar Programa - <span class="text-info">Programas de asignaturas</span></h3>
                </div>
                <div class="card-body">
                    
                    
                    <div class="row justify-content-md-center">
                        <div class="col-sm-5">
                            <label for="carrera">Carrera</label>
                            <select id="carrera" name="carrera" class="selectpicker" data-width="100%" data-live-search="true" required="" title="Seleccione Carrera" data-none-results-text="No se encontraron resultados" data-size="5">
                                <?php
                                if (!empty($carreras)) {
                                    
                                            foreach ($carreras as $carrera) {
                                                echo '<option value="' . $carrera->getId() . '">'.$carrera->getId().' - '.$carrera->getNombre().'</option>';
                                            }
                                        
                                }
                                ?>
                            </select>
                        </div>

                    </div>
                    <br>
                    <?php
                        // Mostramos mensaje, resultado de la operacion de haber aprobado o no un programa.
                        if (isset($_SESSION['mensajeRevisarPrograma'])) {
                            echo $_SESSION['mensajeRevisarPrograma'];
                            unset($_SESSION['mensajeRevisarPrograma']); //
                        }
                    ?>
                    <div id="tabProgramas">
                        <?php 
                            // Esta línea llama a la función con el filtro dinámico ya preparado para la carga inicial
                            echo obtenerProgramasAsignaturasRecientes($query); 
                        ?>
                    </div>
                    <br>
                    
                    
                        
                </div>
            </div>
        </div>
          <?php include_once '../gui/footer.php'; ?>
        
        <script>
            $(document).ready(function(){
                      $('#carrera').change(function () {
                        var codCarrera = $('#carrera').val();
                        // constante que almacena el rol del usuario logueado en el sistema
                        const rol = "<?php echo $rol; ?>";
                        $.ajax({
                          type: 'POST',
                          url: '../lib/consultaAjax/revisarPrograma/tablaProgramasAsignaturas.php',
                          data: {'codCarrera': codCarrera,
                                'rol': rol}
                        })
                        .done(function(programas){
                          $('#tabProgramas').html(programas);
                          // Inicializar DataTable en la respuesta AJAX (asumiendo que tablaProgramasAsignaturas.php devuelve la estructura de tabla)
                          $('#tablaPrograma').DataTable({
                                language: {
                                    url: '../lib/datatable/es-ar.json'
                                }
                          });
                        })
                        .fail(function(){
                          alert('Hubo un error al cargar los Programas de Asignaturas.')
                        });
                      });
                              
                      // Inicializar DataTable en la carga inicial
                      $('#tablaPrograma').DataTable({
                            language: {
                                url: '../lib/datatable/es-ar.json'
                            }
                      });
            });
        </script>
        
    </body>
</html>