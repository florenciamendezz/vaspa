<?php
error_reporting(0); // desactivamos los warning

include_once __DIR__ . '/../../../controlSistema/ManejadorAsignatura.php';
include_once __DIR__ . '/../../../controlSistema/ManejadorProgramaPDF.php';
include_once __DIR__ . '/../../../modeloSistema/Asignatura.Class.php';
include_once __DIR__ . '/../../../modeloSistema/ProgramaPDF.Class.php';
include_once __DIR__ . '/../../../modeloSistema/ProgramaPDFDetalle.Class.php';

$anio = isset($_POST['anio']) ? trim($_POST['anio']) : '';
$codCarrera = isset($_POST['idCarrera']) ? trim($_POST['idCarrera']) : '';

if (empty($anio) || empty($codCarrera)) {
    echo '';
    exit;
}

$manejadorAsignatura = new ManejadorAsignatura();
$asignaturas = $manejadorAsignatura->getAsignaturasDeCarrera($codCarrera, $anio);

$html = '';

// Comprobamos si hay asignaturas
if (is_null($asignaturas) || empty($asignaturas)){
    $html = '<div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
        No se encontraron asignaturas para la carrera y a&ntilde;o seleccionados.
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>';
} else {
    // Creamos el manejador de los programas pdf con soporte de multivigencia y triple aprobación
    $manejadorProgramaPDF = new ManejadorProgramaPDF($codCarrera, $anio);
    
    $html = '
<div class="row justify-content-center"><button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target=".bd-example-modal-lg">Ver Programas de Asignaturas</button></div>

<div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLongTitle">Visualizar Programa de Asignatura</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">';
        
    // Buscador y filtro de solo disponibles
    $html .= '<div class="form-row align-items-end mb-3">
                <div class="col-md-8">
                    <label for="buscador"><b>Buscador</b></label>
                    <input type="text" name="buscador" id="buscador" 
                           class="form-control" placeholder="Ingrese nombre &oacute; el c&oacute;digo de la asignatura a buscar...">
                </div>
                <div class="col-md-4 text-md-right mt-2 mt-md-0">
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input" id="filtroSoloDisponibles">
                        <label class="custom-control-label font-weight-bold" for="filtroSoloDisponibles" style="cursor: pointer;">Solo con programa disponible</label>
                    </div>
                </div>
              </div>';
    
    $html .= '<table class="table table-hover table-sm">
                        <thead>
                            <tr class="table-info">
                                <th class="text-center">C&oacute;digo</th>
                                <th class="text-center">Asignatura</th>
                                <th class="text-center">Visualizar</th>
                            </tr>
                        </thead>
                        <tbody>';

    foreach ($asignaturas as $asignatura) { 
        $ruta = $manejadorProgramaPDF->tieneProgramaPDF($asignatura->getId());
        $tienePdf = !empty($ruta);
        $disponibleAttr = $tienePdf ? '1' : '0';

        $html .= '<tr class="fila-asig" data-disponible="'.$disponibleAttr.'"><td class="text-center">'.$asignatura->getId().'</td>';
        $html .= '<td>'.$asignatura->getNombre().'</td>';
        
        if ($tienePdf){ 
            $prog = $manejadorProgramaPDF->getProgramaVigente($asignatura->getId());
            $tooltip = ($prog && $prog->getAnio() != $anio) 
                ? "Visualizar Programa de Asignatura (Vigente desde el a&ntilde;o {$prog->getAnio()})" 
                : "Visualizar Programa de Asignatura";
            $html .= '<td><div class="row justify-content-center">
                          <a title="'.$tooltip.'" href="'.$ruta.'" target="_blank">
                              <button type="button" class="btn btn-outline-success">
                                <span class="oi oi-document"></span>
                              </button>
                          </a>
                          </div>
                      </td>';
        } else {
            $html .= '<td><div class="row justify-content-center">
                         <a title="Programa no disponible">
                            <button type="button" class="btn btn-outline-success" disabled="">
                              <span class="oi oi-document"></span>
                            </button>
                         </a>
                         </div>
                      </td>';
        }                        
                                    
        $html .= '</tr>';                                
    }

    $html .= '</tbody></table>';
    $html .= '<div id="noResultMessage" class="alert alert-warning text-center" role="alert" style="display: none;">
                        No se han encontrado resultados.
                    </div>';
    $html .= '</div>
    </div>
  </div>
</div>';
}

echo $html;
?>
    <script>
        $(document).ready(function() {
            function filtrarTabla() {
                var texto = $('#buscador').val().toLowerCase().trim();
                var soloDisponibles = $('#filtroSoloDisponibles').is(':checked');
                var visibles = 0;

                $('table tbody tr.fila-asig').each(function() {
                    var $fila = $(this);
                    var esDisponible = $fila.attr('data-disponible') === '1';
                    var contenidoTexto = $fila.text().toLowerCase();

                    var cumpleDisponibilidad = !soloDisponibles || esDisponible;
                    var cumpleBusqueda = (texto === '') || (contenidoTexto.indexOf(texto) !== -1);

                    if (cumpleDisponibilidad && cumpleBusqueda) {
                        $fila.show();
                        visibles++;
                    } else {
                        $fila.hide();
                    }
                });

                if (visibles === 0) {
                    $('#noResultMessage').show();
                } else {
                    $('#noResultMessage').hide();
                }
            }

            $('#buscador').on('keyup input', function() {
                filtrarTabla();
            });

            $('#filtroSoloDisponibles').on('change', function() {
                filtrarTabla();
            });
        });
    </script>
