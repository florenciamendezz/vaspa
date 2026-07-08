<?php
include_once 'BDConexionSistema.Class.php';

/**
 * Description of Profesor
 *
 * @author Francisco
 */
class Profesor {
    private $id;
    private $dni;
    private $nombre;
    private $apellido;
    private $email;
    private $categoria;
    private $preferencias;
    private $idDepartamento;
    
    private $query;

    /**
     *
     * @var mysqli_result
     */
    private $datos;
    
    function __construct($id = null, $datos = null) {
        //Si vienen datos del formulario (Alta) setea valores de Objeto
        if (isset($datos)) {
            //$this->setId($datos['id']);
            //id = NULL debido a que el id es autoincremental
            $this->setId(NULL);
            //$this->setDni($datos['dni']);
            $this->setNombre($datos['nombre']);
            $this->setApellido($datos['apellido']);
            $this->setEmail($datos['email']);
            $this->setCategoria($datos['categoria']);
            //$this->setPreferencias($datos['preferencias']);
            // Nulo debido a que es una dato que no es de interes en nuestro sistema
            $this->setPreferencias(NULL);
            $this->setIdDepartamento($datos['idDepartamento']);
        } else {
            //Sino viene un Objeto, lo recupero (para Modificar)
            if (isset($id)) {
                $this->recuperaObjeto($id);
            } else {
                return false;
            }
        }
        
    }
    
    function recuperaObjeto($id) {
        $this->id = $id;
        $this->query = "SELECT * FROM PROFESOR WHERE id = '{$this->id}'";
        
        $this->datos = BDConexionSistema::getInstancia()->query($this->query);
        $this->datos = $this->datos->fetch_assoc();
        
        foreach ($this->datos as $atributo => $valor) {
            $this->{$atributo} = $valor;
        }
        unset($this->query);
        unset($this->datos);
    }    
            
    function getId() {
        return $this->id;
    }

    function getDni() {
        return $this->dni;
    }

    function getNombre() {
        return $this->nombre;
    }

    function getApellido() {
        return $this->apellido;
    }

    function getEmail() {
        return $this->email;
    }

    function getCategoria() {
        return $this->categoria;
    }

    function getIdDepartamento() {
        return $this->idDepartamento;
    }

    function setId($id) {
        $this->id = $id;
    }

    function setDni($dni) {
        $this->dni = $dni;
    }

    function setNombre($nombre) {
        $this->nombre = $nombre;
    }

    function setApellido($apellido) {
        $this->apellido = $apellido;
    }

    function setEmail($email) {
        $this->email = $email;
    }

    function setCategoria($categoria) {
        $this->categoria = $categoria;
    }

    function setIdDepartamento($idDepartamento) {
        $this->idDepartamento = $idDepartamento;
    }

    function getPreferencias() {
        return $this->preferencias;
    }

    function setPreferencias($preferencias) {
        $this->preferencias = $preferencias;
    }
    
    function getNombreCompleto(){
        return $this->apellido.', '.$this->nombre;
    }

    // Funcion que retorna en un array las asignaturas en las cuales el profesor
    // es responsable. Si no es responsable de asignaturas devuelve NULL
    /**
     * 
     * @return Asignatura[]
     */
    function obtenerAsignaturas(){
        // importamos la clase Asignatura
        include_once __DIR__.'/Asignatura.Class.php';
        //La constante __DIR__ retorna la ruta absoluta del directorio donde se encuentra el fichero que la está utilizando. Y dirname() retorna el directorio padre, en combinación dirname(__DIR__) nos retornaría la ruta absoluta del directorio padre donde se encuentra el fichero que la está usando.
        
        // obtenemos las asignaturas en donde es responsable el profesor
        $this->query = "SELECT a.* "
                . "FROM asignatura a "
                . "INNER JOIN asignatura_responsable ar ON a.id = ar.idAsignatura "
                . "WHERE ar.idProfesor = '{$this->id}'";
                
        $this->datos = BDConexionSistema::getInstancia()->query($this->query);
        
        // validamos el resultado de la query (si retorna false -> Ocurrio un error en la BD) Lanzamos una Excepcion informando el Error
        if (!$this->datos) {
            throw new Exception("Ocurrio un Error al obtener las Asignaturas del Profesor: {$this->apellido}, '{$this->nombre}'.");
        }
        
        $asignaturas = NULL;
        
        if ($this->datos->num_rows > 0) {
            for ($x = 0; $x < $this->datos->num_rows; $x++) {
                $asignaturas[] = $this->datos->fetch_object("Asignatura"); // creamos objeto
            }
        }

        unset($this->query);
        unset($this->datos);

        return $asignaturas;
    }
    
    function obtenerAsignaturasDePlanVigente(){
        // importamos la clase Asignatura
        include_once __DIR__.'/Asignatura.Class.php';
        
        $anioActual = date("Y"); // anio actual tomado del servidor
        
        // Obtenemos las asignaturas vigentes y el plan correspondiente (o materias sin plan asociado) que estén activas en la carrera
        $this->query = "SELECT DISTINCT a.*, pl.id AS idPlan, pl.anio_inicio AS anioInicioPlan, pl.anio_fin AS anioFinPlan 
        FROM profesor p 
        INNER JOIN asignatura_responsable ar ON p.id = ar.idProfesor 
        INNER JOIN asignatura a ON ar.idAsignatura = a.id
        INNER JOIN carrera_asignatura ca ON a.id = ca.idAsignatura AND ca.activo = 1
        LEFT JOIN plan_asignatura pa ON a.id = pa.idAsignatura
        LEFT JOIN plan pl ON pa.idPlan = pl.id
        WHERE p.id = '{$this->id}' 
          AND (
               pl.id IS NULL 
               OR ((pl.anio_inicio <= '{$anioActual}' AND (pl.anio_fin >= '{$anioActual}' OR pl.anio_fin IS NULL)))
          )
        ORDER BY a.nombre ASC, pl.id ASC";
            
        $this->datos = BDConexionSistema::getInstancia()->query($this->query);
        
        // validamos el resultado de la query (si retorna false -> Ocurrio un error en la BD) Lanzamos una Excepcion informando el Error
        if (!$this->datos) {
            throw new Exception("Ocurrio un Error al obtener las Asignaturas del Profesor: {$this->apellido}, '{$this->nombre}'.");
        }
        
        $asignaturas = NULL;
        
        if ($this->datos->num_rows > 0) {
            while ($row = $this->datos->fetch_assoc()) {
                // Instanciamos el objeto Asignatura con sus datos base
                $asigObj = new Asignatura($row['id']);
                // Seteamos las propiedades dinámicas del plan
                $asigObj->setIdPlan($row['idPlan']);
                $asigObj->setAnioInicioPlan($row['anioInicioPlan']);
                $asigObj->setAnioFinPlan($row['anioFinPlan']);
                
                $asignaturas[] = $asigObj;
            }
        }

        unset($this->query);
        unset($this->datos);

        return $asignaturas;
    }
    
}
