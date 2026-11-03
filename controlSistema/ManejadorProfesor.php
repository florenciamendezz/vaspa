<?php

include_once '../modeloSistema/BDConexionSistema.Class.php';
include_once '../modeloSistema/Profesor.Class.php';

/**
 * Description of ManejadorProfesor
 *
 * @author francisco
 */
class ManejadorProfesor {

    private $query;

    /**
     *
     * @var mysqli_result
     */
    private $datos;

    /**
     *
     * @var Profesor[] 
     */
    protected $coleccion;

    function __construct() {
        $this->setColeccion();
    }

    //Metodo que crea la coleccion Profesores
    function setColeccion() {
        $this->query = "SELECT * FROM PROFESOR";
        $this->datos = BDConexionSistema::getInstancia()->query($this->query);

        for ($x = 0; $x < $this->datos->num_rows; $x++) {
            $this->addElemento($this->datos->fetch_object("Profesor"));
        }
    }

    function addElemento($elemento_) {
        $this->coleccion[] = $elemento_;
    }

    /**
     * 
     * @return Profesor[]
     */
    function getColeccion() {
        return $this->coleccion;
    }

    //Funcion para Alta de Profesores que no son responsables de asignaturas (No inserta usuario)
    function alta($datos) {

        //Creo objeto sin enviar ID y enviando todos los datos del formulario
        $Profesor = new Profesor(null, $datos);
        $db = BDConexionSistema::getInstancia();

        if ($this->chequearEmail($Profesor->getEmail())) {
            $stmt = $db->prepare("INSERT INTO PROFESOR (dni, nombre, apellido, email, categoria, preferencias, idDepartamento) VALUES ('0', ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                return false;
            }
            $nom = $Profesor->getNombre();
            $ape = $Profesor->getApellido();
            $mail = $Profesor->getEmail();
            $cat = $Profesor->getCategoria();
            $pref = $Profesor->getPreferencias();
            $depto = intval($Profesor->getIdDepartamento());
            $stmt->bind_param("sssssi", $nom, $ape, $mail, $cat, $pref, $depto);
            $consulta = $stmt->execute();
            $stmt->close();
            return $consulta;
        } else {
            throw new Exception("El email:  <b>" . $Profesor->getEmail() . "</b> ya corresponde a un profesor en la Base de Datos");
        }
    }

    //Funcion para Alta de Profesores que son responsables de asignaturas (lo registra como usuario tambien)
    function altaUsuarioProfesor($datos) {
        $db = BDConexionSistema::getInstancia();

        if ($db->begin_transaction()) {
            $db->autocommit(false);

            // INSERCION PROFESOR
            $Profesor = new Profesor(null, $datos);

            if ($this->chequearEmail($Profesor->getEmail())) {
                $stmtProf = $db->prepare("INSERT INTO PROFESOR (dni, nombre, apellido, email, categoria, preferencias, idDepartamento) VALUES ('0', ?, ?, ?, ?, ?, ?)");
                if (!$stmtProf) {
                    $db->rollback();
                    throw new Exception("Error al preparar alta de profesor.");
                }
                $nom = $Profesor->getNombre();
                $ape = $Profesor->getApellido();
                $mail = $Profesor->getEmail();
                $cat = $Profesor->getCategoria();
                $pref = $Profesor->getPreferencias();
                $depto = intval($Profesor->getIdDepartamento());
                $stmtProf->bind_param("sssssi", $nom, $ape, $mail, $cat, $pref, $depto);
                $consulta = $stmtProf->execute();
                $stmtProf->close();
            } else {
                $db->rollback();
                throw new Exception("El email:  <b>" . $Profesor->getEmail() . "</b> ya corresponde a un profesor en la Base de Datos");
            }

            if (!$consulta) {
                $db->rollback();
                throw new Exception("No se puedo dar de alta el profesor (Error en la Base de Datos).");
            }

            // INSERCION USUARIO
            $nombreUsr = $datos["nombreUsuario"] ?? '';
            $emailUsr = $datos["email"] ?? '';
            $stmtUsr = $db->prepare("INSERT INTO " . Constantes::BD_USERS . ".usuario (id, nombre, email) VALUES (NULL, ?, ?)");
            if (!$stmtUsr) {
                $db->rollback();
                throw new Exception("Error al preparar alta de usuario.");
            }
            $stmtUsr->bind_param("ss", $nombreUsr, $emailUsr);
            if (!$stmtUsr->execute()) {
                $stmtUsr->close();
                $db->rollback();
                throw new Exception("Error al insertar el usuario: " . $db->error);
            }
            $idUsuario = (int) $db->insert_id;
            $stmtUsr->close();

            // INSERCION USUARIO_ROL
            $stmtRol = $db->prepare("INSERT INTO " . Constantes::BD_USERS . ".usuario_rol (id_usuario, id_rol) VALUES (?, 9)");
            if (!$stmtRol) {
                $db->rollback();
                throw new Exception("Error al preparar rol de usuario.");
            }
            $stmtRol->bind_param("i", $idUsuario);
            if (!$stmtRol->execute()) {
                $stmtRol->close();
                $db->rollback();
                throw new Exception("Error al asignar el rol al usuario: " . $db->error);
            }
            $stmtRol->close();

            $db->commit();
            $db->autocommit(true);

            return TRUE;
        } else {
            throw new Exception("No se puedo dar de alta el profesor (Error en la Base de Datos).");
        }
    }

    function baja($id_) {
        $db = BDConexionSistema::getInstancia();
        $idInt = intval($id_);
        $stmt = $db->prepare("DELETE FROM PROFESOR WHERE id = ?");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("i", $idInt);
        $consulta = $stmt->execute();
        $stmt->close();
        return $consulta;
    }

    function bajaUsuarioProfesor($idProfesor, $idUsuario) {
        $db = BDConexionSistema::getInstancia();

        if ($db->begin_transaction()) {
            $db->autocommit(false);
            $idUsrInt = intval($idUsuario);
            $idProfInt = intval($idProfesor);

            $stmtRol = $db->prepare("DELETE FROM " . Constantes::BD_USERS . ".usuario_rol WHERE id_usuario = ?");
            if (!$stmtRol) {
                $db->rollback();
                throw new Exception("No se pudo eliminar el rol del usuario.");
            }
            $stmtRol->bind_param("i", $idUsrInt);
            if (!$stmtRol->execute()) {
                $stmtRol->close();
                $db->rollback();
                throw new Exception("No se pudo eliminar el usuario (Error en la Base de Datos).");
            }
            $stmtRol->close();

            $stmtUsr = $db->prepare("DELETE FROM " . Constantes::BD_USERS . ".usuario WHERE id = ?");
            if (!$stmtUsr) {
                $db->rollback();
                throw new Exception("No se pudo preparar borrado de usuario.");
            }
            $stmtUsr->bind_param("i", $idUsrInt);
            if (!$stmtUsr->execute()) {
                $stmtUsr->close();
                $db->rollback();
                throw new Exception("No se pudo eliminar el usuario (Error en la Base de Datos).");
            }
            $stmtUsr->close();

            $stmtProf = $db->prepare("DELETE FROM PROFESOR WHERE id = ?");
            if (!$stmtProf) {
                $db->rollback();
                throw new Exception("No se pudo preparar borrado de profesor.");
            }
            $stmtProf->bind_param("i", $idProfInt);
            if (!$stmtProf->execute()) {
                $stmtProf->close();
                $db->rollback();
                throw new Exception("No se pudo eliminar el usuario (Error en la Base de Datos).");
            }
            $stmtProf->close();

            $db->commit();
            $db->autocommit(true);

            return TRUE;
        } else {
            throw new Exception("No se pudo eliminar el usuario (Error en la Base de Datos).");
        }
    }

    //Funcion para Modificación de Profesores
    function modificacion($datos, $id_) {
        $Profesor = new Profesor(null, $datos);
        $profesorAntes = new Profesor($id_, NULL);
        $db = BDConexionSistema::getInstancia();
        $idInt = intval($id_);
        $nom = $Profesor->getNombre();
        $ape = $Profesor->getApellido();
        $mail = $Profesor->getEmail();
        $depto = intval($Profesor->getIdDepartamento());

        if ($profesorAntes->getEmail() != $Profesor->getEmail()) {
            if ($this->chequearEmail($mail)) {
                $stmt = $db->prepare("UPDATE PROFESOR SET dni = '0', nombre = ?, apellido = ?, email = ?, idDepartamento = ? WHERE id = ?");
                if (!$stmt) {
                    return false;
                }
                $stmt->bind_param("ssssi", $nom, $ape, $mail, $depto, $idInt);
                $consulta = $stmt->execute();
                $stmt->close();
                return $consulta;
            } else {
                throw new Exception("El email:  <b>" . $mail . "</b> ya corresponde a un profesor en la Base de Datos");
            }
        } else {
            $stmt = $db->prepare("UPDATE PROFESOR SET dni = '0', nombre = ?, apellido = ?, idDepartamento = ? WHERE id = ?");
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param("sssi", $nom, $ape, $depto, $idInt);
            $consulta = $stmt->execute();
            $stmt->close();
            return $consulta;
        }
    }

    //Funcion para Modificación de Profesores que son usuarios del sistema
    function modificacionUsuarioProfesor($datos, $id_, $idUsuario) {
        $Profesor = new Profesor(null, $datos);
        $profesorAntes = new Profesor($id_, NULL);
        $db = BDConexionSistema::getInstancia();
        $idInt = intval($id_);
        $idUsrInt = intval($idUsuario);
        $nom = $Profesor->getNombre();
        $ape = $Profesor->getApellido();
        $mail = $Profesor->getEmail();
        $depto = intval($Profesor->getIdDepartamento());

        if ($profesorAntes->getEmail() != $Profesor->getEmail()) {
            if ($this->chequearEmail($mail)) {

                // iniciamos transaccion
                $db->autocommit(false);
                $db->begin_transaction();

                // actualizamos datos del profesor
                $stmtProf = $db->prepare("UPDATE PROFESOR SET dni = '0', nombre = ?, apellido = ?, email = ?, idDepartamento = ? WHERE id = ?");
                if (!$stmtProf) {
                    $db->rollback();
                    throw new Exception("No se pudo preparar modificación de profesor.");
                }
                $stmtProf->bind_param("ssssi", $nom, $ape, $mail, $depto, $idInt);
                if (!$stmtProf->execute()) {
                    $stmtProf->close();
                    $db->rollback();
                    throw new Exception("No se pudo modificar los datos del profesor (Error en la Base de Datos).");
                }
                $stmtProf->close();

                // actualizamos datos del usuario del profesor
                $stmtUsr = $db->prepare("UPDATE " . Constantes::BD_USERS . ".usuario SET email = ? WHERE id = ?");
                if (!$stmtUsr) {
                    $db->rollback();
                    throw new Exception("No se pudo preparar modificación del usuario.");
                }
                $stmtUsr->bind_param("si", $mail, $idUsrInt);
                if (!$stmtUsr->execute()) {
                    $stmtUsr->close();
                    $db->rollback();
                    throw new Exception("No se pudo modificar los datos del profesor (Error en la Base de Datos).");
                }
                $stmtUsr->close();

                $db->commit();
                $db->autocommit(true);

                return TRUE;
            } else {
                throw new Exception("El email:  <b>" . $mail . "</b> ya corresponde a un profesor en la Base de Datos");
            }
        } else {
            $stmt = $db->prepare("UPDATE PROFESOR SET dni = '0', nombre = ?, apellido = ?, idDepartamento = ? WHERE id = ?");
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param("sssi", $nom, $ape, $depto, $idInt);
            $consulta = $stmt->execute();
            $stmt->close();
            return $consulta;
        }
    }

    function chequearEmail($email_) {
        $db = BDConexionSistema::getInstancia();
        $stmt = $db->prepare("SELECT 1 FROM PROFESOR WHERE email = ? LIMIT 1");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("s", $email_);
        $stmt->execute();
        $this->resultado = $stmt->get_result();
        $existe = ($this->resultado && $this->resultado->num_rows == 1);
        $stmt->close();
        return !$existe;
    }

    function getEstadoProfesorProgramasPDF($idProfesor) {
        include_once '../controlSistema/ManejadorAsignatura.php';
        include_once '../controlSistema/ManejadorProgramaPDF.php';

        //estado = 0 → ningun programa cargado
        //estado>0 y estado<100 → 1 o más programas cargados pero no todos
        //estado = 100 → 100% 
        $estado = 0;
        $programasCargados = 0;
        $ManejadorAsignatura = new ManejadorAsignatura();
        $ManejadorProgramaPDF = new ManejadorProgramaPDF();
        $Asignaturas[] = $ManejadorAsignatura->getAsignaturasSegunProfesor($idProfesor);
        $cantAsignaturas = sizeof($Asignaturas);

        foreach ($Asignaturas as $Asignatura) {
            $idAsignatura = $Asignatura->getId();
            $ruta = $ManejadorProgramaPDF->tieneProgramaPDF($idAsignatura);
            if($ruta != ""){
                $programasCargados++;
            }
        }

        return $programasCargados;
    }
    
    function getProfesoresResponsables(){
        $profesores = [];
        $this->query = "SELECT DISTINCT idProfesor FROM asignatura_responsable";
        $this->datos = BDConexionSistema::getInstancia()->query($this->query);

        if ($this->datos && $this->datos->num_rows > 0) {
            for ($x = 0; $x < $this->datos->num_rows; $x++) {
                $result = $this->datos->fetch_array();
                $profesor = new Profesor($result['idProfesor']);
                array_push($profesores, $profesor);
            }
        }
        return $profesores;
    }

}
