<?php

include_once __DIR__ . '/../modeloSistema/ProgramaPDFDetalle.Class.php';

/**
 * Description of ManejadorProgramaPDF
 *
 * @author Francisco
 */
class ManejadorProgramaPDF {
    private $query;

    /**
     *
     * @var mysqli_result
     */
    private $datos;

    /**
     *
     * @var ProgramaPDFDetalle[] 
     */
    protected $coleccion;

    function __construct($codCarrera, $anio) {
        $this->setColeccion($codCarrera, $anio);
    }
    
    function setColeccion($codCarrera, $anio) {
        // Modificado para usar programa_pdf_detalle, verificar triple aprobación y soportar multivigencia
        $anioInt = intval($anio);
        $this->query = "SELECT * FROM programa_pdf_detalle 
                        WHERE anio <= '{$anioInt}' 
                        AND (anio + CAST(vigencia AS UNSIGNED) - 1) >= '{$anioInt}' 
                        AND aprobado_va = 1 
                        AND aprobado_depto = 1 
                        AND aprobado_escuela = 1
                        ORDER BY anio DESC";
                        
        $this->datos = BDConexionSistema::getInstancia()->query($this->query);

        if ($this->datos) {
            for ($x = 0; $x < $this->datos->num_rows; $x++) {
                $row = $this->datos->fetch_assoc();
                $programa = new ProgramaPDFDetalle($row['id']); 
                $this->addElemento($programa);
            }
        }
    }

    function addElemento($elemento_) {
        $this->coleccion[] = $elemento_;
    }

    /**
     * 
     * @return ProgramaPDFDetalle[]
     */
    function getColeccion() {
        return $this->coleccion;
    }
    
    /**
     * 
     * @return ProgramaPDFDetalle|null
     */
    function getProgramaVigente($codAsignatura) {
        if (!is_null($this->coleccion)) {
            foreach ($this->coleccion as $programaPDF) {
                if (intval($programaPDF->getIdAsignatura()) == intval($codAsignatura)) {
                    return $programaPDF;
                }
            }
        }
        return null;
    }

    /**
     * 
     * @return string
     */
    function tieneProgramaPDF($codAsignatura){
        //En caso de que tenga programa la materia vamos a devolver la ruta a dicho programa, caso contrario
        //retornamos un string vacio el cual va a indicar de que dicha asignatura no tiene programa 
        $ruta = "";
        $prog = $this->getProgramaVigente($codAsignatura);
        if ($prog) {
            $ruta = "../archivos/programas/" . $prog->getRutaArchivo();
        }
        return $ruta;
    }
    
}
