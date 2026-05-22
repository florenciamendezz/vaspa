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
    private $aprobadoVaFirma;
    private $subidoPorRol;
    private $comentarioDesaprobacion;
    private $fechaActualizacion;
    private $fechaUltimoMovimientoCircuito;

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
            $this->aprobadoVaFirma = $fila['aprobado_va_firma'];
            $this->subidoPorRol = $fila['subido_por_rol'];
            $this->comentarioDesaprobacion = $fila['comentario_desaprobacion'];
            $this->fechaActualizacion = $fila['fecha_actualizacion'];
            $this->fechaUltimoMovimientoCircuito = $fila['fecha_ultimo_movimiento_circuito'];
        }
    }

    public function crear($idAsignatura, $anio, $vigencia, $rutaArchivo) {
        $conexion = BDConexionSistema::getInstancia();
        $conexion->autocommit(FALSE); // Iniciar transaccion

        try {
            $fechaCarga = date('Y-m-d');
            $circuito = $this->determinarCircuito($idAsignatura);
            $aprobadoEscuelaVal = ($circuito == 'estandar') ? "NULL" : "1";
            
            $idAsignaturaEscaped = $conexion->real_escape_string($idAsignatura);
            $anioEscaped = $conexion->real_escape_string($anio);
            $vigenciaEscaped = $conexion->real_escape_string($vigencia);
            $rutaArchivoEscaped = $conexion->real_escape_string($rutaArchivo);

            // 1. Insertar en tabla PROGRAMA (Legacy)
            $sqlPrograma = "INSERT INTO programa (idAsignatura, anio, vigencia, fechaCarga, ubicacion, 
                                                   horasTeoria, horasPractica, horasOtros, regimenCursada,
                                                   fundamentacion, objetivosGenerales, organizacionContenidos, criteriosEvaluacion,
                                                   metodologiaPresencial, regularizacionPresencial, aprobacionPresencial,
                                                   metodologiaSATEP, regularizacionSATEP, aprobacionSATEP,
                                                   metodologiaLibre, aprobacionLibre,
                                                   aprobadoVa, aprobadoDepto, aprobadoEscuela, enRevision, fueDesaprobado,
                                                   comentarioVa, comentarioDepto, comentarioEscuela) 
                            VALUES ('{$idAsignaturaEscaped}', '{$anioEscaped}', '{$vigenciaEscaped}', '{$fechaCarga}', 'DPTO',
                                    '00:00:00', '00:00:00', '00:00:00', 'A',
                                    '', '', '', '',
                                    '', '', '',
                                    '', '', '',
                                    '', '',
                                    NULL, NULL, {$aprobadoEscuelaVal}, 0, 0,
                                    '', '', '')";
            
            if (!$conexion->query($sqlPrograma)) {
                throw new Exception("Error al insertar en tabla programa: " . $conexion->error);
            }
            
            // 2. Insertar en tabla PROGRAMA_PDF_DETALLE
            $sqlDetalle = "INSERT INTO programa_pdf_detalle (id_asignatura, anio, vigencia, ruta_archivo, fecha_carga, aprobado_escuela, subido_por_rol) 
                           VALUES ('{$idAsignaturaEscaped}', '{$anioEscaped}', '{$vigenciaEscaped}', '{$rutaArchivoEscaped}', '{$fechaCarga}', {$aprobadoEscuelaVal}, 'profesor')";
            
            if (!$conexion->query($sqlDetalle)) {
                throw new Exception("Error al insertar en tabla programa_pdf_detalle: " . $conexion->error);
            }

            $this->id = $conexion->insert_id; // Obtener ID del ultimo insert (programa_pdf_detalle)
            $conexion->commit();
            $conexion->autocommit(TRUE);
            
            $this->cargarDatos();
            return true;

        } catch (Exception $e) {
            $conexion->rollback();
            $conexion->autocommit(TRUE);
            error_log($e->getMessage());
            return false;
        }
    }

    public static function obtenerPorAsignaturaYAnio($idAsignatura, $anio) {
        $conexion = BDConexionSistema::getInstancia();
        $idAsignaturaEscaped = $conexion->real_escape_string($idAsignatura);
        $anioEscaped = $conexion->real_escape_string($anio);
        
        $sql = "SELECT id FROM programa_pdf_detalle 
                WHERE id_asignatura = '{$idAsignaturaEscaped}' AND anio = '{$anioEscaped}' 
                ORDER BY id DESC LIMIT 1";
        $resultado = $conexion->query($sql);
        if ($resultado && $resultado->num_rows > 0) {
            $fila = $resultado->fetch_assoc();
            return new ProgramaPDFDetalle($fila['id']);
        }
        return null;
    }

    public function obtenerEstadoActual() {
        if ($this->fueDesaprobado) {
            return "Devuelto al Profesor";
        }
        if (!$this->enRevision) {
            if ($this->aprobadoVaFirma) {
                return "Aprobado";
            }
            if ($this->aprobadoDepto) {
                return "Revisado por Departamento";
            }
            if ($this->aprobadoVa) {
                return "Revisado por VA";
            }
            if ($this->aprobadoEscuela) {
                $circuito = $this->determinarCircuito($this->idAsignatura);
                if ($circuito == 'estandar') {
                    return "Revisado por Escuela";
                }
            }
            return "Borrador";
        } else {
            // enRevision = 1
            if ($this->aprobadoEscuela === null) {
                return "Pendiente de revisión de Escuela";
            }
            if ($this->aprobadoVa === null) {
                return "Pendiente de revisión VA";
            }
            if ($this->aprobadoDepto === null) {
                return "Pendiente de revisión de Departamento";
            }
            if ($this->aprobadoVaFirma === null) {
                return "Pendiente de firma final VA";
            }
        }
        return "Borrador";
    }

    public function determinarCircuito($idAsignatura) {
        $conexion = BDConexionSistema::getInstancia();
        
        // 1. Verificar si la asignatura es institucional
        $idAsignaturaEscaped = $conexion->real_escape_string($idAsignatura);
        $sqlAsignatura = "SELECT es_institucional FROM asignatura WHERE id = '{$idAsignaturaEscaped}'";
        $resultadoAsignatura = $conexion->query($sqlAsignatura);
        if ($resultadoAsignatura && $resultadoAsignatura->num_rows > 0) {
            $fila = $resultadoAsignatura->fetch_assoc();
            if ($fila['es_institucional'] == 1) {
                return 'institucional';
            }
        }
        
        // 2. Verificar si está activa la vacancia de Escuela en configuracion_sistema
        $sqlConfig = "SELECT valor FROM configuracion_sistema WHERE clave = 'vacancia_escuela'";
        $resultadoConfig = $conexion->query($sqlConfig);
        if ($resultadoConfig && $resultadoConfig->num_rows > 0) {
            $filaConfig = $resultadoConfig->fetch_assoc();
            if ($filaConfig['valor'] == '1') {
                return 'vacancia';
            }
        }
        
        return 'estandar';
    }

    public function revisorSubePdfFirmado($rol, $nombreArchivo) {
        $campoTabla = "";
        $campoLegacy = "";
        $subidoPorRolVal = "";
        
        if ($rol == 'Vinculación Académica' || $rol == 'Administrador') {
            $campoTabla = "aprobado_va";
            $campoLegacy = "aprobadoVa";
            $subidoPorRolVal = "va";
        } elseif ($rol == 'Director de Departamento') {
            $campoTabla = "aprobado_depto";
            $campoLegacy = "aprobadoDepto";
            $subidoPorRolVal = "depto";
        } elseif ($rol == 'Secretario de Escuela' || $rol == 'Director de Escuela') {
            $campoTabla = "aprobado_escuela";
            $campoLegacy = "aprobadoEscuela";
            $subidoPorRolVal = "escuela";
        } else {
            return false;
        }

        $conexion = BDConexionSistema::getInstancia();
        $conexion->autocommit(FALSE);

        try {
            $nombreArchivoEscaped = $conexion->real_escape_string($nombreArchivo);
            
            // 1. Actualizar PROGRAMA_PDF_DETALLE
            $sql = "UPDATE programa_pdf_detalle 
                    SET ruta_archivo = '{$nombreArchivoEscaped}',
                        {$campoTabla} = 1,
                        en_revision = 0,
                        fue_desaprobado = 0,
                        subido_por_rol = '{$subidoPorRolVal}',
                        fecha_ultimo_movimiento_circuito = NOW()
                    WHERE id = {$this->id}";
            if (!$conexion->query($sql)) throw new Exception("Error updating PDF detail on upload: " . $conexion->error);

            // 2. Actualizar PROGRAMA (Legacy)
            $idLegacy = $this->getProgramaLegacyId();
            if ($idLegacy) {
                $sqlLegacy = "UPDATE programa SET {$campoLegacy} = 1, enRevision = 0, fueDesaprobado = 0 WHERE id = {$idLegacy}";
                if (!$conexion->query($sqlLegacy)) throw new Exception("Error updating Legacy program on upload: " . $conexion->error);
            }

            $conexion->commit();
            $conexion->autocommit(TRUE);
            
            $this->cargarDatos();
            return true;
        } catch (Exception $e) {
            $conexion->rollback();
            $conexion->autocommit(TRUE);
            error_log($e->getMessage());
            return false;
        }
    }

    public function enviarAlSiguiente() {
        $conexion = BDConexionSistema::getInstancia();
        $conexion->autocommit(FALSE);

        try {
            // 1. Actualizar PROGRAMA_PDF_DETALLE
            $sql = "UPDATE programa_pdf_detalle 
                    SET en_revision = 1,
                        fecha_ultimo_movimiento_circuito = NOW()
                    WHERE id = {$this->id}";
            if (!$conexion->query($sql)) throw new Exception("Error advancing PDF detail: " . $conexion->error);

            // 2. Actualizar PROGRAMA (Legacy)
            $idLegacy = $this->getProgramaLegacyId();
            if ($idLegacy) {
                $sqlLegacy = "UPDATE programa SET enRevision = 1 WHERE id = {$idLegacy}";
                if (!$conexion->query($sqlLegacy)) throw new Exception("Error advancing Legacy program: " . $conexion->error);
            }

            $conexion->commit();
            $conexion->autocommit(TRUE);
            
            $this->cargarDatos();
            return true;
        } catch (Exception $e) {
            $conexion->rollback();
            $conexion->autocommit(TRUE);
            error_log($e->getMessage());
            return false;
        }
    }

    public function aprobar($rol) {
        $campoTabla = "";
        $campoLegacy = "";
        $subidoPorRolVal = "";
        
        if ($rol == 'Vinculación Académica' || $rol == 'Administrador') {
            if ($this->aprobadoDepto == 1) {
                $campoTabla = "aprobado_va_firma";
                $campoLegacy = ""; // No hay columna equivalente en la tabla legacy
                $subidoPorRolVal = "va_firma";
            } else {
                $campoTabla = "aprobado_va";
                $campoLegacy = "aprobadoVa";
                $subidoPorRolVal = "va";
            }
        } elseif ($rol == 'Director de Departamento') {
            $campoTabla = "aprobado_depto";
            $campoLegacy = "aprobadoDepto";
            $subidoPorRolVal = "depto";
        } elseif ($rol == 'Secretario de Escuela' || $rol == 'Director de Escuela') {
            $campoTabla = "aprobado_escuela";
            $campoLegacy = "aprobadoEscuela";
            $subidoPorRolVal = "escuela";
        } else {
            return false;
        }
        
        $conexion = BDConexionSistema::getInstancia();
        $conexion->autocommit(FALSE);

        try {
            $fechaMovimientoSql = "";
            if ($campoTabla == "aprobado_va_firma") {
                $fechaMovimientoSql = ", fecha_ultimo_movimiento_circuito = NOW()";
            }
            
            // 1. Actualizar PROGRAMA_PDF_DETALLE
            $sql = "UPDATE programa_pdf_detalle 
                    SET {$campoTabla} = 1, 
                        en_revision = 0, 
                        fue_desaprobado = 0, 
                        subido_por_rol = '{$subidoPorRolVal}'
                        {$fechaMovimientoSql}
                    WHERE id = {$this->id}";
            if (!$conexion->query($sql)) throw new Exception("Error updating PDF detail: " . $conexion->error);

            // 2. Actualizar PROGRAMA (Legacy)
            $idLegacy = $this->getProgramaLegacyId();
            if ($idLegacy && $campoLegacy) {
                $sqlLegacy = "UPDATE programa SET {$campoLegacy} = 1, enRevision = 0, fueDesaprobado = 0 WHERE id = {$idLegacy}";
                if (!$conexion->query($sqlLegacy)) throw new Exception("Error updating Legacy program: " . $conexion->error);
            }

            $conexion->commit();
            $conexion->autocommit(TRUE);
            
            $this->cargarDatos();
            return true;
        } catch (Exception $e) {
            $conexion->rollback();
            $conexion->autocommit(TRUE);
            error_log($e->getMessage());
            return false;
        }
    }

    public function desaprobar($rol, $comentario) {
        $conexion = BDConexionSistema::getInstancia();
        $conexion->autocommit(FALSE);

        try {
            $circuito = $this->determinarCircuito($this->idAsignatura);
            $aprobadoEscuelaVal = ($circuito == 'estandar') ? "NULL" : "1";
            $comentarioEscaped = $conexion->real_escape_string($comentario);
            
            // 1. Actualizar PROGRAMA_PDF_DETALLE
            $sql = "UPDATE programa_pdf_detalle 
                    SET aprobado_escuela = {$aprobadoEscuelaVal},
                        aprobado_va = NULL,
                        aprobado_depto = NULL,
                        aprobado_va_firma = NULL,
                        en_revision = 0,
                        fue_desaprobado = 1,
                        comentario_desaprobacion = '{$comentarioEscaped}',
                        subido_por_rol = NULL,
                        fecha_ultimo_movimiento_circuito = NOW()
                    WHERE id = {$this->id}";
                    
            if (!$conexion->query($sql)) throw new Exception("Error updating PDF detail: " . $conexion->error);

            // 2. Actualizar PROGRAMA (Legacy)
            $idLegacy = $this->getProgramaLegacyId();
            if ($idLegacy) {
                $campoComentarioLegacy = "";
                if ($rol == 'Vinculación Académica' || $rol == 'Administrador') {
                    $campoComentarioLegacy = "comentarioVa";
                } elseif ($rol == 'Director de Departamento') {
                    $campoComentarioLegacy = "comentarioDepto";
                } elseif ($rol == 'Secretario de Escuela' || $rol == 'Director de Escuela') {
                    $campoComentarioLegacy = "comentarioEscuela";
                }
                
                $sqlLegacy = "UPDATE programa 
                              SET aprobadoEscuela = {$aprobadoEscuelaVal},
                                  aprobadoVa = NULL,
                                  aprobadoDepto = NULL,
                                  enRevision = 0,
                                  fueDesaprobado = 1";
                if ($campoComentarioLegacy) {
                    $sqlLegacy .= ", {$campoComentarioLegacy} = '{$comentarioEscaped}'";
                }
                $sqlLegacy .= " WHERE id = {$idLegacy}";
                
                if (!$conexion->query($sqlLegacy)) throw new Exception("Error updating Legacy program: " . $conexion->error);
            }

            $conexion->commit();
            $conexion->autocommit(TRUE);
            
            $this->cargarDatos();
            return true;
        } catch (Exception $e) {
            $conexion->rollback();
            $conexion->autocommit(TRUE);
            error_log($e->getMessage());
            return false;
        }
    }

    public function resetearParaReentrega() {
        $conexion = BDConexionSistema::getInstancia();
        $conexion->autocommit(FALSE);

        try {
            $circuito = $this->determinarCircuito($this->idAsignatura);
            $aprobadoEscuelaVal = ($circuito == 'estandar') ? "NULL" : "1";
            
            // 1. Actualizar PROGRAMA_PDF_DETALLE
            $sql = "UPDATE programa_pdf_detalle 
                    SET aprobado_escuela = {$aprobadoEscuelaVal},
                        aprobado_va = NULL,
                        aprobado_depto = NULL,
                        aprobado_va_firma = NULL,
                        en_revision = 0,
                        fue_desaprobado = 0,
                        comentario_desaprobacion = NULL,
                        subido_por_rol = NULL,
                        fecha_ultimo_movimiento_circuito = NOW()
                    WHERE id = {$this->id}";
            if (!$conexion->query($sql)) throw new Exception("Error resetting PDF detail for re-delivery: " . $conexion->error);

            // 2. Actualizar PROGRAMA (Legacy)
            $idLegacy = $this->getProgramaLegacyId();
            if ($idLegacy) {
                $sqlLegacy = "UPDATE programa 
                              SET aprobadoEscuela = {$aprobadoEscuelaVal},
                                  aprobadoVa = NULL,
                                  aprobadoDepto = NULL,
                                  enRevision = 0,
                                  fueDesaprobado = 0,
                                  comentarioVa = '',
                                  comentarioDepto = '',
                                  comentarioEscuela = ''
                              WHERE id = {$idLegacy}";
                if (!$conexion->query($sqlLegacy)) throw new Exception("Error resetting Legacy program for re-delivery: " . $conexion->error);
            }

            $conexion->commit();
            $conexion->autocommit(TRUE);
            
            $this->cargarDatos();
            return true;
        } catch (Exception $e) {
            $conexion->rollback();
            $conexion->autocommit(TRUE);
            error_log($e->getMessage());
            return false;
        }
    }

    public function getProgramaLegacyId() {
        $sql = "SELECT id FROM programa WHERE idAsignatura = '{$this->idAsignatura}' AND anio = {$this->anio}";
        $res = BDConexionSistema::getInstancia()->query($sql);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            return $row['id'];
        }
        return null;
    }

    public function actualizarArchivo($idAsignatura, $anio, $nombreArchivo) {
        $conexion = BDConexionSistema::getInstancia();
        $idAsignaturaEscaped = $conexion->real_escape_string($idAsignatura);
        $anioEscaped = $conexion->real_escape_string($anio);
        $nombreArchivoEscaped = $conexion->real_escape_string($nombreArchivo);
        
        $sqlId = "SELECT id FROM programa_pdf_detalle WHERE id_asignatura = '{$idAsignaturaEscaped}' AND anio = {$anioEscaped}";
        $resId = conexion->query($sqlId);
        
        if ($resId && $resId->num_rows > 0) {
            $row = $resId->fetch_assoc();
            $id = $row['id'];
            
            $sql = "UPDATE programa_pdf_detalle SET ruta_archivo = '{$nombreArchivoEscaped}' WHERE id = {$id}";
            return $conexion->query($sql);
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
    public function getAprobadoVaFirma() { return $this->aprobadoVaFirma; }
    public function getSubidoPorRol() { return $this->subidoPorRol; }
    public function getComentarioDesaprobacion() { return $this->comentarioDesaprobacion; }
    public function getFechaActualizacion() { return $this->fechaActualizacion; }
    public function getFechaUltimoMovimientoCircuito() { return $this->fechaUltimoMovimientoCircuito; }

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
    public function setAprobadoVaFirma($aprobadoVaFirma) { $this->aprobadoVaFirma = $aprobadoVaFirma; }
    public function setSubidoPorRol($subidoPorRol) { $this->subidoPorRol = $subidoPorRol; }
    public function setComentarioDesaprobacion($comentarioDesaprobacion) { $this->comentarioDesaprobacion = $comentarioDesaprobacion; }
    public function setFechaActualizacion($fechaActualizacion) { $this->fechaActualizacion = $fechaActualizacion; }
    public function setFechaUltimoMovimientoCircuito($fechaUltimoMovimientoCircuito) { $this->fechaUltimoMovimientoCircuito = $fechaUltimoMovimientoCircuito; }
}
?>
