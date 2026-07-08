<?php
include_once '../../../modeloSistema/BDConexionSistema.Class.php';
include_once '../../../lib/ControlAcceso.Class.php';

if (!ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_CARRERAS)) {
    echo "Permiso denegado.";
    exit;
}

if (isset($_POST['codCarrera'])) {
    $codCarrera = $_POST['codCarrera'];
    $db = BDConexionSistema::getInstancia();

    // Obtener departamentos
    $resDeptos = $db->query("SELECT * FROM departamento ORDER BY nombre ASC");
    $deptos = [];
    while ($row = $resDeptos->fetch_assoc()) {
        $deptos[] = $row;
    }

    // Obtener profesores
    $resProfs = $db->query("SELECT * FROM profesor ORDER BY apellido ASC, nombre ASC");
    $profs = [];
    while ($row = $resProfs->fetch_assoc()) {
        $profs[] = $row;
    }

    // Obtener materias asociadas a la carrera
    $query = "SELECT a.id, a.nombre, a.idDepartamento, ca.activo 
              FROM carrera_asignatura ca
              INNER JOIN asignatura a ON ca.idAsignatura = a.id
              WHERE ca.idCarrera = '{$codCarrera}'
              ORDER BY a.nombre ASC";
    $resMaterias = $db->query($query);

    if (!$resMaterias) {
        echo '<div class="alert alert-danger text-center">Error al consultar las materias.</div>';
        exit;
    }

    if ($resMaterias->num_rows == 0) {
        echo '<div class="alert alert-warning text-center">La carrera seleccionada no tiene materias asociadas.</div>';
        exit;
    }
    
    // Tabla HTML
    ?>
    <div class="table-responsive table-premium mt-3">
        <table class="table table-hover table-striped mb-0 align-middle" id="tablaCarreraMaterias">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Asignatura</th>
                    <th>Departamento</th>
                    <th>Profesores Responsables</th>
                    <th class="text-center">Activa</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($materia = $resMaterias->fetch_assoc()) {
                    $idAsignatura = $materia['id'];
                    $nombreAsignatura = $materia['nombre'];
                    $idDepto = $materia['idDepartamento'];
                    $activo = $materia['activo'];

                    // Obtener los responsables de esta materia
                    $resResp = $db->query("SELECT idProfesor FROM asignatura_responsable WHERE idAsignatura = '{$idAsignatura}'");
                    $responsablesIds = [];
                    while ($resp = $resResp->fetch_assoc()) {
                        $responsablesIds[] = $resp['idProfesor'];
                    }
                    ?>
                    <tr data-id="<?php echo $idAsignatura; ?>">
                        <td class="font-weight-bold"><?php echo $idAsignatura; ?></td>
                        <td><?php echo $nombreAsignatura; ?></td>
                        <td>
                            <select class="selectpicker depto-select" data-width="100%" data-live-search="true" data-container="body">
                                <?php foreach ($deptos as $d) {
                                    $selected = ($d['id'] == $idDepto) ? 'selected' : '';
                                    echo '<option value="'.$d['id'].'" '.$selected.'>'.$d['nombre'].'</option>';
                                } ?>
                            </select>
                        </td>
                        <td>
                            <select class="selectpicker responsables-select" data-width="100%" data-live-search="true" multiple title="Sin responsables" data-actions-box="true" data-container="body">
                                <?php foreach ($profs as $p) {
                                    $selected = in_array($p['id'], $responsablesIds) ? 'selected' : '';
                                    echo '<option value="'.$p['id'].'" '.$selected.'>'.$p['apellido'].', '.$p['nombre'].'</option>';
                                } ?>
                            </select>
                        </td>
                        <td class="text-center">
                            <label class="toggle-switch">
                                <input type="checkbox" class="toggle-activo" data-id="<?php echo $idAsignatura; ?>" <?php echo ($activo == 1) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-danger btn-sm btn-quitar-materia" data-id="<?php echo $idAsignatura; ?>" data-nombre="<?php echo $nombreAsignatura; ?>">
                                <span class="oi oi-trash"></span> Quitar
                            </button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php
}
