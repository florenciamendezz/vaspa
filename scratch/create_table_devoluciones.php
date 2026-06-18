<?php
include_once '../modeloSistema/BDConexionSistema.Class.php';

$sql = "CREATE TABLE IF NOT EXISTS `programa_devoluciones` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `id_programa` INT UNSIGNED NULL, -- programa.id is INT UNSIGNED
  `id_programa_pdf` INT NULL, -- programa_pdf_detalle.id is INT (signed)
  `id_usuario` INT UNSIGNED NOT NULL,
  `rol_revisor` VARCHAR(50) NOT NULL,
  `fecha` DATETIME NOT NULL,
  `comentario` TEXT NOT NULL,
  `leido` TINYINT(1) DEFAULT 0,
  `resuelto` TINYINT(1) DEFAULT 0,
  CONSTRAINT `fk_devolucion_programa` FOREIGN KEY (`id_programa`) REFERENCES `programa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_devolucion_programa_pdf` FOREIGN KEY (`id_programa_pdf`) REFERENCES `programa_pdf_detalle` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$conexion = BDConexionSistema::getInstancia();
if ($conexion->query($sql)) {
    echo "Tabla programa_devoluciones creada exitosamente.";
} else {
    echo "Error al crear la tabla: " . $conexion->error;
}
?>
