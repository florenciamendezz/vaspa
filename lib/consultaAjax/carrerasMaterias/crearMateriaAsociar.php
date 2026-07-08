<?php
include_once '../../../modeloSistema/BDConexionSistema.Class.php';
include_once '../../../lib/ControlAcceso.Class.php';

if (!ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_CARRERAS)) {
    echo json_encode(['success' => false, 'error' => 'Permiso denegado.']);
    exit;
}

if (isset($_POST['codCarrera']) && isset($_POST['id']) && isset($_POST['nombre']) && isset($_POST['idDepartamento'])) {
    $codCarrera = $_POST['codCarrera'];
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $idDepartamento = $_POST['idDepartamento'];
    $horasSemanales = 0; // valor nulo o por defecto, al definirse luego en el PDF
    $contenidosMinimos = ''; // vacío, al detallarse en el PDF analítico
    $responsables = isset($_POST['responsables']) ? $_POST['responsables'] : [];

    $db = BDConexionSistema::getInstancia();

    // 1. Validar que no exista
    $resCheck = $db->query("SELECT id FROM asignatura WHERE id = '{$id}'");
    if ($resCheck && $resCheck->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Ya existe una asignatura con el código ' . $id]);
        exit;
    }

    // 2. Iniciar transacción
    $db->begin_transaction();

    try {
        // 3. Insertar asignatura (sin columna idProfesor que ya no existe)
        $stmt = $db->prepare("INSERT INTO asignatura (id, nombre, idDepartamento, contenidosMinimos, horasSemanales) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisi", $id, $nombre, $idDepartamento, $contenidosMinimos, $horasSemanales);
        if (!$stmt->execute()) {
            throw new Exception("Error al insertar asignatura: " . $stmt->error);
        }

        // 4. Insertar responsables en asignatura_responsable
        if (!empty($responsables)) {
            $stmtResp = $db->prepare("INSERT INTO asignatura_responsable (idAsignatura, idProfesor) VALUES (?, ?)");
            foreach ($responsables as $respId) {
                $stmtResp->bind_param("ss", $id, $respId);
                if (!$stmtResp->execute()) {
                    throw new Exception("Error al insertar responsable: " . $stmtResp->error);
                }
            }
        }

        // 5. Insertar relación en carrera_asignatura
        $stmtCarrera = $db->prepare("INSERT INTO carrera_asignatura (idCarrera, idAsignatura, activo) VALUES (?, ?, b'1')");
        $stmtCarrera->bind_param("ss", $codCarrera, $id);
        if (!$stmtCarrera->execute()) {
            throw new Exception("Error al asociar asignatura a la carrera: " . $stmtCarrera->error);
        }

        $db->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $db->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Parámetros incompletos.']);
}
