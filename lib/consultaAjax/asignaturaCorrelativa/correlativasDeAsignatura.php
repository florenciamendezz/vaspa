<?php
//  EN ESTE SCRIPT SE MUESTRA UNA TABLA EN LA CUAL SE PRESENTAN LAS ASIGNATURAS CORRELATIVAS DE LA ASIGNATURA SELECCIONADA

include_once '../../../modeloSistema/BDConexionSistema.Class.php';
include_once '../../../modeloSistema/Asignatura.Class.php';

$idAsignatura = trim($_GET['idAsignatura'] ?? '');
$idPlan = trim($_GET['idPlan'] ?? '');

$db = BDConexionSistema::getInstancia();
$idAsigEsc = $db->real_escape_string($idAsignatura);
$idPlanEsc = $db->real_escape_string($idPlan);

// 1. Precedentes: la materia actual requiere que el alumno haya cursado/aprobado la materia anterior
$sqlPrecedentes = "SELECT c.id AS idCorrelativaDe, c.requisito, 'Precedente' AS tipo, a.id AS codAsignatura, a.nombre AS nombreAsignatura
                   FROM correlativa_de c
                   JOIN asignatura a ON c.idAsignatura_Correlativa_Anterior = a.id
                   WHERE c.idAsignatura = '{$idAsigEsc}' AND c.idPlan = '{$idPlanEsc}'";

// 2. Subsiguientes: otras materias requieren que el alumno haya cursado/aprobado la materia actual
$sqlSubsiguientes = "SELECT c.id AS idCorrelativaDe, c.requisito, 'Subsiguiente' AS tipo, a.id AS codAsignatura, a.nombre AS nombreAsignatura
                     FROM correlativa_de c
                     JOIN asignatura a ON c.idAsignatura = a.id
                     WHERE c.idAsignatura_Correlativa_Anterior = '{$idAsigEsc}' AND c.idPlan = '{$idPlanEsc}'";

$resPrec = $db->query($sqlPrecedentes);
$resSub = $db->query($sqlSubsiguientes);

$correlativas = [];
if ($resPrec) {
    while ($row = $resPrec->fetch_assoc()) {
        $correlativas[] = $row;
    }
}
if ($resSub) {
    while ($row = $resSub->fetch_assoc()) {
        $correlativas[] = $row;
    }
}

if (empty($correlativas)) {
    echo '<div class="alert alert-info text-center" role="alert">
            <span class="oi oi-info"></span> No hay correlativas registradas para esta asignatura en este plan de estudio.
            <br>Utilice el formulario superior para agregar correlatividades precedentes o subsiguientes.
          </div>';
} else {
?>
    <div class="table-responsive">
        <table class="table table-hover table-bordered table-sm" id="tablaCorrelativasAsig">
            <thead class="thead-light">
                <tr>
                    <th style="width: 10%;">C&oacute;digo</th>
                    <th>Asignatura</th>
                    <th style="width: 22%;">Requisito</th>
                    <th style="width: 18%;">Tipo de Correlatividad</th>
                    <th style="width: 15%;" class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($correlativas as $c) { 
                    $badgeReq = ($c['requisito'] === 'Aprobada') ? 'badge-success' : 'badge-primary';
                    $badgeTipo = ($c['tipo'] === 'Precedente') ? 'badge-info' : 'badge-secondary';
                    $otroReq = ($c['requisito'] === 'Aprobada') ? 'Regular' : 'Aprobada';
                ?>
                <tr>
                    <td class="font-weight-bold"><?= htmlspecialchars($c['codAsignatura']); ?></td>
                    <td><?= htmlspecialchars($c['nombreAsignatura']); ?></td>
                    <td>
                        <span class="badge <?= $badgeReq; ?> p-2 mr-2"><?= htmlspecialchars($c['requisito']); ?></span>
                        <button type="button" class="btn btn-outline-secondary btn-sm" title="Cambiar a <?= $otroReq; ?>" onclick="cambiarRequisito(<?= intval($c['idCorrelativaDe']); ?>, '<?= $otroReq; ?>')">
                            <span class="oi oi-loop"></span> Cambiar a <?= $otroReq; ?>
                        </button>
                    </td>
                    <td>
                        <span class="badge <?= $badgeTipo; ?> p-2"><?= htmlspecialchars($c['tipo']); ?></span>
                        <small class="text-muted d-block">
                            <?= ($c['tipo'] === 'Precedente') ? 'Materia previa requerida' : 'Materia posterior'; ?>
                        </small>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm" title="Eliminar correlatividad" onclick="eliminarCorrelativa(<?= intval($c['idCorrelativaDe']); ?>, '<?= htmlspecialchars($c['nombreAsignatura'], ENT_QUOTES); ?>', '<?= htmlspecialchars($c['tipo'], ENT_QUOTES); ?>')">
                            <span class="oi oi-trash"></span> Eliminar
                        </button>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <script type="text/javascript">
        $(document).ready(function () {
            if ($.fn.DataTable.isDataTable('#tablaCorrelativasAsig')) {
                $('#tablaCorrelativasAsig').DataTable().destroy();
            }
            $('#tablaCorrelativasAsig').DataTable({
                language: {
                    url: '../lib/datatable/es-ar.json'
                },
                pageLength: 25,
                order: [[3, 'asc'], [1, 'asc']]
            });
        });
    </script>
<?php
}
?>
