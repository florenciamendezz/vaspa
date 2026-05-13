<?php
include_once __DIR__ . '/BDConexionSistema.Class.php';

class ProgramaPDFDetalle {
    private $id;
    private $idAsignatura;
    private $anio;
    private $vigencia;
    private $rutaArchivo;
    private $fechaCarga;
    private $enRevision;
    private $aprobadoVa;
    private $aprobadoDepto;
    private $aprobadoEscuela;
    private $fueDesaprobado;

    public function __construct($id = null) {
        if ($id) {
            $this->id = $id;
            $this->cargarDatos();
        }
    }

    private function cargarDatos() {
        $sql = "SELECT * FROM programa_pdf_detalle WHERE id = {$this->id}";
        $resultado = BDConexionSistema::getInstancia()->query($sql);
        if ($resultado && $resultado->num_rows > 0) {
            $fila = $resultado->fetch_assoc();
            $this->idAsignatura = $fila['id_asignatura'];
            $this->anio = $fila['anio'];
            $this->vigencia = $fila['vigencia'];
            $this->rutaArchivo = $fila['ruta_archivo'];
            $this->fechaCarga = $fila['fecha_carga'];
            $this->enRevision = $fila['en_revision'];
            $this->aprobadoVa = $fila['aprobado_va'];
            $this->aprobadoDepto = $fila['aprobado_depto'];
            $this->aprobadoEscuela = $fila['aprobado_escuela'];
            $this->fueDesaprobado = $fila['fue_desaprobado'];
        }
    }

    public function crear($idAsignatura, $anio, $vigencia, $rutaArchivo) {
        $conexion = BDConexionSistema::getInstancia();
        $conexion->autocommit(FALSE); // Iniciar transaccion

        try {
            $fechaCarga = date('Y-m-d');
            
            // 1. Insertar en tabla PROGRAMA (Legacy)
            // Se insertan valores minimos requeridos y defaults para evitar errores
            // Se agregan campos de texto vacios para cumplir con NOT NULL
            // Se agregan flags de estado
            $sqlPrograma = "INSERT INTO programa (idAsignatura, anio, vigencia, fechaCarga, ubicacion, 
                                                  horasTeoria, horasPractica, horasOtros, regimenCursada,
                                                  fundamentacion, objetivosGenerales, organizacionContenidos, criteriosEvaluacion,
                                                  metodologiaPresencial, regularizacionPresencial, aprobacionPresencial,
                                                  metodologiaSATEP, regularizacionSATEP, aprobacionSATEP,
                                                  metodologiaLibre, aprobacionLibre,
                                                  aprobadoVa, aprobadoDepto, aprobadoEscuela, enRevision, fueDesaprobado,
                                                  comentarioVa, comentarioDepto, comentarioEscuela) 
                            VALUES ('{$idAsignatura}', '{$anio}', '{$vigencia}', '{$fechaCarga}', 'DPTO',
                                    '00:00:00', '00:00:00', '00:00:00', 'A',
                                    '', '', '', '',
                                    '', '', '',
                                    '', '', '',
                                    '', '',
                                    NULL, NULL, NULL, 0, 0,
                                    '', '', '')";
            
            if (!$conexion->query($sqlPrograma)) {
                throw new Exception("Error al insertar en tabla programa: " . $conexion->error);
            }
            
            // 2. Insertar en tabla PROGRAMA_PDF_DETALLE
            $sqlDetalle = "INSERT INTO programa_pdf_detalle (id_asignatura, anio, vigencia, ruta_archivo, fecha_carga) 
                           VALUES ('{$idAsignatura}', '{$anio}', '{$vigencia}', '{$rutaArchivo}', '{$fechaCarga}')";
            
            if (!$conexion->query($sqlDetalle)) {
                throw new Exception("Error al insertar en tabla programa_pdf_detalle: " . $conexion->error);
            }

            $this->id = $conexion->insert_id; // Obtener ID del ultimo insert (programa_pdf_detalle)
            $conexion->commit();
            $conexion->autocommit(TRUE);
            return true;

        } catch (Exception $e) {
            $conexion->rollback();
            $conexion->autocommit(TRUE);
            // Log error or handle it
            error_log($e->getMessage());
            return false;
        }
    }

    public static function obtenerPorAsignaturaYAnio($idAsignatura, $anio) {
        $sql = "SELECT id FROM programa_pdf_detalle WHERE id_asignatura = {$idAsignatura} AND anio = {$anio}";
        $resultado = BDConexionSistema::getInstancia()->query($sql);
        if ($resultado && $resultado->num_rows > 0) {
            $fila = $resultado->fetch_assoc();
            return new ProgramaPDFDetalle($fila['id']);
        }
        return null;
    }

    public function aprobar($rol) {
        $campoTabla = "";
        $campoLegacy = "";
        
        if ($rol == 'Vinculación Académica' || $rol == 'Administrador') {
            $campoTabla = "aprobado_va";
            $campoLegacy = "aprobadoVa";
        } elseif ($rol == 'Director de Departamento') {
            $campoTabla = "aprobado_depto";
            $campoLegacy = "aprobadoDepto";
        } elseif ($rol == 'Secretario de Escuela' || $rol == 'Director de Escuela') {
            $campoTabla = "aprobado_escuela";
            $campoLegacy = "aprobadoEscuela";
        } else {
            return false;
        }
        
        $conexion = BDConexionSistema::getInstancia();
        $conexion->autocommit(FALSE);

        try {
            // 1. Actualizar PROGRAMA_PDF_DETALLE
            $sql = "UPDATE programa_pdf_detalle SET {$campoTabla} = 1, en_revision = 0, fue_desaprobado = 0 WHERE id = {$this->id}";
            if (!$conexion->query($sql)) throw new Exception("Error updating PDF detail");

            // 2. Actualizar PROGRAMA (Legacy)
            $idLegacy = $this->getProgramaLegacyId();
            if ($idLegacy) {
                // Mapeo de columnas legacy (camelCase vs snake_case si aplica, pero aqui parecen ser camelCase en legacy)
                // Revisando schema: aprobadoVa, aprobadoDepto, aprobadoEscuela
                $sqlLegacy = "UPDATE programa SET {$campoLegacy} = 1, enRevision = 0, fueDesaprobado = 0 WHERE id = {$idLegacy}";
                if (!$conexion->query($sqlLegacy)) throw new Exception("Error updating Legacy program");
            }

            $conexion->commit();
            $conexion->autocommit(TRUE);
            return true;
        } catch (Exception $e) {
            $conexion->rollback();
            $conexion->autocommit(TRUE);
            return false;
        }
    }

    public function desaprobar($rol, $comentario) {
        $campoTabla = "";
        $campoLegacy = "";
        $campoComentarioLegacy = "";
        
        if ($rol == 'Vinculación Académica' || $rol == 'Administrador') {
            $campoTabla = "aprobado_va";
            $campoLegacy = "aprobadoVa";
            $campoComentarioLegacy = "comentarioVa";
        } elseif ($rol == 'Director de Departamento') {
            $campoTabla = "aprobado_depto";
            $campoLegacy = "aprobadoDepto";
            $campoComentarioLegacy = "comentarioDepto";
        } elseif ($rol == 'Secretario de Escuela' || $rol == 'Director de Escuela') {
            $campoTabla = "aprobado_escuela";
            $campoLegacy = "aprobadoEscuela";
            $campoComentarioLegacy = "comentarioEscuela";
        } else {
            return false;
        }

        $conexion = BDConexionSistema::getInstancia();
        $conexion->autocommit(FALSE);

        try {
            // 1. Actualizar PROGRAMA_PDF_DETALLE
            $sql = "UPDATE programa_pdf_detalle SET {$campoTabla} = 0, en_revision = 0, fue_desaprobado = 1 WHERE id = {$this->id}";
            if (!$conexion->query($sql)) throw new Exception("Error updating PDF detail");

            // 2. Actualizar PROGRAMA (Legacy)
            $idLegacy = $this->getProgramaLegacyId();
            if ($idLegacy) {
                $comentarioEscaped = $conexion->real_escape_string($comentario);
                $sqlLegacy = "UPDATE programa SET {$campoLegacy} = 0, enRevision = 0, fueDesaprobado = 1, {$campoComentarioLegacy} = '{$comentarioEscaped}' WHERE id = {$idLegacy}";
                if (!$conexion->query($sqlLegacy)) throw new Exception("Error updating Legacy program");
            }

            $conexion->commit();
            $conexion->autocommit(TRUE);
            return true;
        } catch (Exception $e) {
            $conexion->rollback();
            $conexion->autocommit(TRUE);
            return false;
        }
    }
    
    public function getProgramaLegacyId() {
        $sql = "SELECT id FROM programa WHERE idAsignatura = {$this->idAsignatura} AND anio = {$this->anio}";
        $res = BDConexionSistema::getInstancia()->query($sql);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            return $row['id'];
        }
        return null;
    }

    public function actualizarArchivo($idAsignatura, $anio, $nombreArchivo) {
        $sqlId = "SELECT id FROM programa_pdf_detalle WHERE id_asignatura = {$idAsignatura} AND anio = {$anio}";
        $resId = BDConexionSistema::getInstancia()->query($sqlId);
        
        if ($resId && $resId->num_rows > 0) {
            $row = $resId->fetch_assoc();
            $id = $row['id'];
            
            $sql = "UPDATE programa_pdf_detalle SET ruta_archivo = '{$nombreArchivo}' WHERE id = {$id}";
            return BDConexionSistema::getInstancia()->query($sql);
        }
        return false;
    }

    public function getId() { return $this->id; }
    public function getIdAsignatura() { return $this->idAsignatura; }
    public function getAnio() { return $this->anio; }
    public function getVigencia() { return $this->vigencia; }
    public function getRutaArchivo() { return $this->rutaArchivo; }
    public function getFechaCarga() { return $this->fechaCarga; }
    public function getEnRevision() { return $this->enRevision; }
    public function getAprobadoVa() { return $this->aprobadoVa; }
    public function getAprobadoDepto() { return $this->aprobadoDepto; }
    public function getAprobadoEscuela() { return $this->aprobadoEscuela; }
    public function getFueDesaprobado() { return $this->fueDesaprobado; }

    public function setIdAsignatura($idAsignatura) { $this->idAsignatura = $idAsignatura; }
    public function setAnio($anio) { $this->anio = $anio; }
    public function setVigencia($vigencia) { $this->vigencia = $vigencia; }
    public function setRutaArchivo($rutaArchivo) { $this->rutaArchivo = $rutaArchivo; }
    public function setFechaCarga($fechaCarga) { $this->fechaCarga = $fechaCarga; }
    public function setEnRevision($enRevision) { $this->enRevision = $enRevision; }
    public function setAprobadoVa($aprobadoVa) { $this->aprobadoVa = $aprobadoVa; }
    public function setAprobadoDepto($aprobadoDepto) { $this->aprobadoDepto = $aprobadoDepto; }
    public function setAprobadoEscuela($aprobadoEscuela) { $this->aprobadoEscuela = $aprobadoEscuela; }
    public function setFueDesaprobado($fueDesaprobado) { $this->fueDesaprobado = $fueDesaprobado; }
}
?>
