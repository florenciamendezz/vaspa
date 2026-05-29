-- 1. Agregar columnas faltantes en programa_pdf_detalle
ALTER TABLE programa_pdf_detalle
  ADD COLUMN aprobado_va_firma BIT(1) NULL
      COMMENT 'Segunda aprobacion de VA (firma final, luego del Depto)'
      AFTER aprobado_depto,
  ADD COLUMN subido_por_rol VARCHAR(50) NULL
      COMMENT 'profesor|escuela|va|depto|va_firma'
      AFTER aprobado_va_firma,
  ADD COLUMN fecha_actualizacion TIMESTAMP
      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      AFTER subido_por_rol,
  ADD COLUMN fecha_ultimo_movimiento_circuito TIMESTAMP NULL
      COMMENT 'Ultimo cambio real de etapa del circuito'
      AFTER fecha_actualizacion;

-- Renombrar columna 'observacion' (sin uso) a 'comentario_desaprobacion'
-- Evita crear una columna nueva duplicada; el campo estaba sin uso en todo el código
ALTER TABLE programa_pdf_detalle
  CHANGE COLUMN observacion comentario_desaprobacion TEXT NULL
  COMMENT 'Comentario del revisor al desaprobar el programa';

-- 2. Corregir inconsistencia de tipo en ambas tablas
ALTER TABLE programa_pdf_detalle
  MODIFY COLUMN aprobado_escuela BIT(1) NULL;

ALTER TABLE programa
  MODIFY COLUMN aprobadoEscuela BIT(1) NULL;

-- 3. Agregar campo es_institucional en asignatura
ALTER TABLE asignatura
  ADD COLUMN es_institucional BIT(1) NOT NULL DEFAULT 0
  COMMENT 'Si es 1, el circuito omite Escuela y comienza directamente en VA';

-- Marcar las asignaturas institucionales conocidas
UPDATE asignatura SET es_institucional = 1
  WHERE id IN ('1108', '0901', '1107');

-- 4. Crear tabla de configuración del sistema
CREATE TABLE IF NOT EXISTS configuracion_sistema (
  clave VARCHAR(100) NOT NULL PRIMARY KEY,
  valor VARCHAR(255) NOT NULL,
  descripcion TEXT NULL,
  actualizado_por INT NULL,
  fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Agregar configuración inicial
INSERT IGNORE INTO configuracion_sistema (clave, valor, descripcion)
  VALUES
    ('vacancia_escuela', '0', 'Si es 1, el paso de Escuela se saltea y los programas van directo a VA'),
    ('asignaturas_ocultas_panel_va', '', 'Lista de IDs de asignaturas sin programa ocultas en el panel VA, separados por coma');

-- 5. Crear usuario Director de Escuela de prueba
INSERT IGNORE INTO usuario (nombre, email)
  VALUES ('Director de Escuela', 'esstefaniamendez+escuela@gmail.com');

INSERT IGNORE INTO usuario_rol (id_usuario, id_rol)
  SELECT id, 12 FROM usuario WHERE email = 'esstefaniamendez+escuela@gmail.com' LIMIT 1;
