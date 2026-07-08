<?php
include_once '../../../modeloSistema/BDConexionSistema.Class.php';
include_once '../../../lib/ControlAcceso.Class.php';

if (!ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_CARRERAS)) {
    echo json_encode(['success' => false, 'error' => 'Permiso denegado.']);
    exit;
}

if (isset($_POST['idAsignatura'])) {
    $idAsignatura = $_POST['idAsignatura'];
    $idDepartamento = isset($_POST['idDepartamento']) ? $_POST['idDepartamento'] : null;
    $responsables = isset($_POST['responsables']) ? $_POST['responsables'] : [];

    $db = BDConexionSistema::getInstancia();

    $db->begin_transaction();

    try {
        // 1. Actualizar departamento si está definido
        if ($idDepartamento !== null) {
            $stmt = $db->prepare("UPDATE asignatura SET idDepartamento = ? WHERE id = ?");
            $stmt->bind_param("is", $idDepartamento, $idAsignatura);
            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar departamento: " . $stmt->error);
            }
        }

        // 2. Limpiar profesores responsables actuales
        $stmtDelete = $db->prepare("DELETE FROM asignatura_responsable WHERE idAsignatura = ?");
        $stmtDelete->bind_param("s", $idAsignatura);
        if (!$stmtDelete->execute()) {
            throw new Exception("Error al limpiar responsables anteriores: " . $stmtDelete->error);
        }

        // 3. Insertar nuevos responsables
        if (!empty($responsables)) {
            $stmtInsert = $db->prepare("INSERT INTO asignatura_responsable (idAsignatura, idProfesor) VALUES (?, ?)");
            foreach ($responsables as $respId) {
                $stmtInsert->bind_param("ss", $idAsignatura, $respId);
                if (!$stmtInsert->execute()) {
                    throw new Exception("Error al insertar responsable: " . $stmtInsert->error);
                }
            }
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
