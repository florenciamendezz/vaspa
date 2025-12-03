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
        
        if ($rol == 'Vinculación Académica' || $rol == 'Administrador') {
            $campoTabla = "aprobado_va";
        } elseif ($rol == 'Director de Departamento') {
            $campoTabla = "aprobado_depto";
        } elseif ($rol == 'Secretario de Escuela') {
            $campoTabla = "aprobado_escuela";
        } else {
            return false;
        }
        
        $sql = "UPDATE programa_pdf_detalle SET {$campoTabla} = 1, en_revision = 0, fue_desaprobado = 0 WHERE id = {$this->id}";
        return BDConexionSistema::getInstancia()->query($sql);
    }

    public function desaprobar($rol, $comentario) {
        // Nota: El comentario se guarda en la tabla programa (legacy) o necesitamos una tabla de comentarios para PDF?
        // Por ahora asumimos que se guarda en la tabla programa legacy si existe, o no se guarda si no hay estructura.
        // Pero el método original parecía tener lógica para esto.
        // Vamos a actualizar el estado en programa_pdf_detalle.
        
        $campoTabla = "";
        
        if ($rol == 'Vinculación Académica' || $rol == 'Administrador') {
            $campoTabla = "aprobado_va";
        } elseif ($rol == 'Director de Departamento') {
            $campoTabla = "aprobado_depto";
        } elseif ($rol == 'Secretario de Escuela') {
            $campoTabla = "aprobado_escuela";
        } else {
            return false;
        }

        $sql = "UPDATE programa_pdf_detalle SET {$campoTabla} = 0, en_revision = 0, fue_desaprobado = 1 WHERE id = {$this->id}";
        return BDConexionSistema::getInstancia()->query($sql);
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
