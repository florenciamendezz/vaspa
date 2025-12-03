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
        // Modificado para usar programa_pdf_detalle y verificar triple aprobación
        // Filtramos por año y por asignaturas que pertenezcan a la carrera (esto último se hace indirectamente al verificar el ID en tieneProgramaPDF, 
        // pero para optimizar podríamos filtrar aquí si tuviéramos la relación. 
        // Por ahora traemos todos los aprobados del año y filtramos en memoria o mejoramos la query).
        // Dado que el diseño original filtraba por nombre LIKE codCarrera, intentaremos mantener esa lógica si es posible, 
        // pero programa_pdf_detalle tiene id_asignatura.
        // Lo más seguro es traer todos los aprobados del año y que tieneProgramaPDF filtre por ID de asignatura.
        
        $this->query = "SELECT * FROM programa_pdf_detalle 
                        WHERE anio = '{$anio}' 
                        AND aprobado_va = 1 
                        AND aprobado_depto = 1 
                        AND aprobado_escuela = 1";
                        
        $this->datos = BDConexionSistema::getInstancia()->query($this->query);

        if ($this->datos) {
            for ($x = 0; $x < $this->datos->num_rows; $x++) {
                $obj = new ProgramaPDFDetalle();
                // Hidratamos el objeto manualmente o usamos un método si existiera. 
                // ProgramaPDFDetalle tiene constructor con ID que carga de BD, pero eso es N+1 queries.
                // Mejor instanciamos y asignamos propiedades si son accesibles, o usamos reflection/métodos.
                // Como las propiedades son privadas y no hay setters para todo, usaremos el constructor con ID 
                // aunque sea ineficiente, O mejor, modificamos ProgramaPDFDetalle para permitir hidratación masiva?
                // Para no tocar más archivos, usaremos el constructor con ID que hace query. 
                // PERO, fetch_object no llama al constructor con parametros.
                // Vamos a usar el ID para instanciarlo correctamente.
                
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
     * @return string
     */
    function tieneProgramaPDF($codAsignatura){
        //En caso de que tenga programa la materia vamos a devolver la ruta a dicho programa, caso contrario
        //retornamos un string vacio el cual va a indicar de que dicha asignatura no tiene programa 
        $ruta = "";
        
        if (!is_null($this->coleccion)){
            foreach ($this->coleccion as $programaPDF){
                // Comparamos id_asignatura directamente
                if ($programaPDF->getIdAsignatura() == $codAsignatura){
                    $ruta = "../archivos/programas/".$programaPDF->getRutaArchivo();
                    break;
                }
            }
        }    
        return $ruta;
    }
    
}
