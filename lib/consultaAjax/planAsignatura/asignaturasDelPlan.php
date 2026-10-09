<?php
//  EN ESTE SCRIPT SE MUESTRA UNA TABLA EN LA CUAL SE PRESENTA LAS ASIGNATURAS DEL CORRESPONDIENTE PLAN

include_once '../../../modeloSistema/Plan.Class.php';
include_once '../../../modeloSistema/Profesor.Class.php';
include_once '../../../modeloSistema/Departamento.Class.php';

$idPlan = $_GET['id'] ?? '';
$plan = new Plan($idPlan);
$asignaturas = $plan->getAsignaturas();

if (is_null($asignaturas) || empty($asignaturas)) {
    echo '<div class="alert alert-info text-center" role="alert">
            <span class="oi oi-info"></span> No hay asignaturas vinculadas a este Plan de Estudio todav&iacute;a.
            <br>Utilice el buscador superior para agregar materias existentes o crearlas al instante.
          </div>';
} else {
?>
    <div class="table-responsive">
        <table class="table table-hover table-bordered table-sm" id="tablaAsignaturasPlan">
            <thead class="thead-light">
                <tr>
                    <th style="width: 10%;">C&oacute;digo</th>
                    <th>Asignatura</th>
                    <th>Departamento</th>
                    <th>Profesor Responsable</th>
                    <th style="width: 18%;" class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($asignaturas as $asignatura) { 
                    $depNombre = "Sin Asignar";
                    if ($asignatura->getIdDepartamento()) {
                        $depObj = new Departamento($asignatura->getIdDepartamento());
                        if ($depObj->getNombre()) $depNombre = $depObj->getNombre();
                    }

                    $profNombre = "Sin Asignar";
                    if ($asignatura->getIdProfesor()) {
                        $profObj = new Profesor($asignatura->getIdProfesor());
                        if ($profObj->getApellido()) $profNombre = $profObj->getApellido() . ', ' . $profObj->getNombre();
                    }
                ?>
                <tr>
                    <td class="font-weight-bold"><?= htmlspecialchars($asignatura->getId()); ?></td>
                    <td><?= htmlspecialchars($asignatura->getNombre()); ?></td>
                    <td><?= htmlspecialchars($depNombre); ?></td>
                    <td><?= htmlspecialchars($profNombre); ?></td>
                    <td class="text-center">
                        <a title="Gestionar Correlatividades" href="../vista/asignaturas.correlativas.php?idAsignatura=<?= urlencode($asignatura->getId()); ?>&idPlan=<?= urlencode($plan->getId()); ?>" class="btn btn-outline-info btn-sm mr-1">
                            <span class="oi oi-list"></span> Correlativas
                        </a>
                        <button type="button" title="Quitar Asignatura del Plan" class="btn btn-outline-danger btn-sm" onclick="quitarAsignatura('<?= htmlspecialchars($asignatura->getId(), ENT_QUOTES); ?>', '<?= htmlspecialchars($asignatura->getNombre(), ENT_QUOTES); ?>')">
                            <span class="oi oi-trash"></span> Quitar
                        </button>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <script type="text/javascript">
        $(document).ready(function () {
            if ($.fn.DataTable.isDataTable('#tablaAsignaturasPlan')) {
                $('#tablaAsignaturasPlan').DataTable().destroy();
            }
            $('#tablaAsignaturasPlan').DataTable({
                language: {
                    url: '../lib/datatable/es-ar.json'
                },
                pageLength: 25,
                order: [[1, 'asc']]
            });
        });
    </script>
<?php
}
?>
