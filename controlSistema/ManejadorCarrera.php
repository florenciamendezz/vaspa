<?php

include_once '../modeloSistema/BDConexionSistema.Class.php';
include_once '../modeloSistema/Carrera.Class.php';

/**
 * Description of ManejadorCarrera
 *
 * @author fabricio
 */
class ManejadorCarrera {

    private $query;

    /**
     *
     * @var mysqli_result
     */
    private $datos, $resultado;

    /**
     *
     * @var Carrera[] 
     */
    protected $coleccion;

    function __construct() {
        $this->setColeccion();
    }

    //Metodo que crea la coleccion Carreras
    function setColeccion() {
        $this->query = "SELECT * FROM CARRERA";
        $this->datos = BDConexionSistema::getInstancia()->query($this->query);

        for ($x = 0; $x < $this->datos->num_rows; $x++) {
            $this->addElemento($this->datos->fetch_object("Carrera"));
        }
    }

    function addElemento($elemento_) {
        $this->coleccion[] = $elemento_;
    }

    /**
     * 
     * @return Carrera[]
     */
    function getColeccion() {
        return $this->coleccion;
    }

    //Funcion para Alta de Carreras
    function alta($datos) {

        //Creo objeto sin enviar ID y enviando todos los datos del formulario
        $Carrera = new Carrera(null, $datos);
        if ($this->validaEspaciosEnBlanco($Carrera->getNombre())) {
            if ($this->chequearExistencia($Carrera->getId())) {
                //Seteo el nuevo ID como el ID que viene del formulario, completado con 0 a la izquierda
                $Carrera->setId($this->completaConCeros($Carrera->getId()));
                $db = BDConexionSistema::getInstancia();
                $stmt = $db->prepare("INSERT INTO CARRERA (id, nombre) VALUES (?, ?)");
                if (!$stmt) {
                    return false;
                }
                $idCar = $Carrera->getId();
                $nomCar = $Carrera->getNombre();
                $stmt->bind_param("ss", $idCar, $nomCar);
                $consulta = $stmt->execute();
                $stmt->close();
                return $consulta;
            } else {
                throw new Exception("El c&oacute;digo  " . $Carrera->getId() . " ya corresponde a una carrera en la Base de Datos");
            }
        } else {
            throw new Exception("El nombre de la carrera no puede estar en blanco");
        }
    }

    function baja($id_) {
        $db = BDConexionSistema::getInstancia();
        $stmt = $db->prepare("DELETE FROM CARRERA WHERE id = ?");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("s", $id_);
        $consulta = $stmt->execute();
        $stmt->close();
        return $consulta;
    }

    //Funcion para Modificación de Carreras
    function modificacion($datos, $id_) {
        $Carrera = new Carrera(null, $datos);
        
        $Carrera->setId($this->completaConCeros($Carrera->getId()));

        if ($this->validaEspaciosEnBlanco($Carrera->getNombre())) {
            $db = BDConexionSistema::getInstancia();
            $nomCar = $Carrera->getNombre();
            $idCar = $Carrera->getId();

            if ($idCar == $id_) {
                $stmt = $db->prepare("UPDATE CARRERA SET nombre = ? WHERE id = ?");
                if (!$stmt) {
                    return false;
                }
                $stmt->bind_param("ss", $nomCar, $id_);
                $consulta = $stmt->execute();
                $stmt->close();
                return $consulta;
            } else {
                if ($this->chequearExistencia($idCar)) {
                    $stmt = $db->prepare("UPDATE CARRERA SET id = ?, nombre = ? WHERE id = ?");
                    if (!$stmt) {
                        return false;
                    }
                    $stmt->bind_param("sss", $idCar, $nomCar, $id_);
                    $consulta = $stmt->execute();
                    $stmt->close();
                    return $consulta;
                } else {
                    throw new Exception("El c&oacute;digo  " . $Carrera->getId() . " ya corresponde a una carrera en la Base de Datos");
                }
            }
        } else {
            throw new Exception("El nombre de la carrera no puede estar en blanco");
        }
    }

    function completaConCeros($id_) {
        //Se convierte a String
        $idCarrera = (String) $id_;

        //Se quitan los 0 a la 
        $idCarrera = ltrim($idCarrera, '0');

        if (strlen($idCarrera) == 2) {
            $idCarrera = "0" . $idCarrera;
        } else {
            if (strlen($idCarrera) == 1) {
                $idCarrera = "00" . $idCarrera;
            }
        }
        return $idCarrera;
    }

    function chequearExistencia($idCarrera_) {
        $db = BDConexionSistema::getInstancia();
        $idCarStr = (string) $idCarrera_;
        $stmt = $db->prepare("SELECT 1 FROM CARRERA WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("s", $idCarStr);
        $stmt->execute();
        $this->resultado = $stmt->get_result();
        $existe = ($this->resultado && $this->resultado->num_rows == 1);
        $stmt->close();
        return !$existe;
    }

    function validaEspaciosEnBlanco($nombre_) {
        $nombre = trim($nombre_);
        if ($nombre == "") {
            return false;
        } else {
            return true;
        }
    }

}
