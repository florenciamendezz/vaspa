# Sistema VASPA (Visualización, Administración y Seguimiento de Programas de Asignaturas)

VASPA es una aplicación web desarrollada para gestionar el circuito secuencial de firmas y aprobación de programas analíticos de asignaturas de la UARG - UNPA.

---

## 📖 Documentación del Proyecto

La documentación funcional y técnica estructurada para la gestión institucional de VASPA se encuentra en la carpeta [/docs](file:///c:/xampp/htdocs/vaspa/docs):

* **[00_fuentes_base_del_proyecto.md](file:///c:/xampp/htdocs/vaspa/docs/00_fuentes_base_del_proyecto.md)**: Detalle de las bases normativas y metodológicas del proyecto.
* **[00_minuta_entrevista_va.md](file:///c:/xampp/htdocs/vaspa/docs/00_minuta_entrevista_va.md)**: Minuta formal de entrevista y definiciones funcionales de Secretaría y Vinculación Académica (Disposición 041/25).
* **[ejemplos/resumen_entrevista_kairos.md](file:///c:/xampp/htdocs/vaspa/docs/ejemplos/resumen_entrevista_kairos.md)**: Documento de referencia de gestión de requerimientos (caso de éxito LDS-2025).
* **[01_requisitos_funcionales.md](file:///c:/xampp/htdocs/vaspa/docs/01_requisitos_funcionales.md)**: Roles, requerimientos del software y especificación detallada del nuevo Dashboard Gerencial.
* **[02_decisiones_confirmadas.md](file:///c:/xampp/htdocs/vaspa/docs/02_decisiones_confirmadas.md)**: Registro histórico de decisiones de diseño y negocio.
* **[03_circuitos_del_sistema.md](file:///c:/xampp/htdocs/vaspa/docs/03_circuitos_del_sistema.md)**: Explicación de los flujos del circuito secuencial (Estándar, Institucional, Vacancia y Devoluciones).
* **[04_modelo_datos_y_migraciones.md](file:///c:/xampp/htdocs/vaspa/docs/04_modelo_datos_y_migraciones.md)**: Detalle técnico de la tabla `programa_pdf_detalle` y significado de los flags lógicos de la BD.
* **[05_plan_implementacion.md](file:///c:/xampp/htdocs/vaspa/docs/05_plan_implementacion.md)**: Plan de implementación y cambios arquitectónicos del Dashboard.
* **[06_casos_de_prueba.md](file:///c:/xampp/htdocs/vaspa/docs/06_casos_de_prueba.md)**: Planilla de pruebas funcionales y automatizadas.
* **[07_registro_de_cambios.md](file:///c:/xampp/htdocs/vaspa/docs/07_registro_de_cambios.md)**: Registro de versiones y cambios del código de desarrollo.
* **[08_pendientes_y_mejoras.md](file:///c:/xampp/htdocs/vaspa/docs/08_pendientes_y_mejoras.md)**: Bitácora de beca y listado de mejoras para futuros desarrollos.

---

## 🛠️ Acceso Rápido para Pruebas (Desarrollo)

Para agilizar el proceso de desarrollo y pruebas locales, se ha implementado una tarjeta de **Acceso Rápido para Pruebas** directamente en la pantalla de bienvenida (`app/index.php`). Esta tarjeta permite simular el inicio de sesión para cada uno de los roles clave del sistema sin depender de la autenticación real de Google.

### ⚠️ IMPORTANTE: Eliminación para Producción

Esta tarjeta y sus funciones asociadas son **estrictamente de carácter de desarrollo/pruebas** y **deben ser removidas del código fuente antes de desplegar el sistema en un entorno productivo**.

#### Elementos a eliminar:
1. **En la vista de login (`app/index.php`)**:
   - Eliminar el bloque marcado bajo los comentarios `<!-- [INICIO: ACCESO RÁPIDO PARA PRUEBAS Y DESARROLLO] -->` hasta `<!-- [FIN: ACCESO RÁPIDO PARA PRUEBAS Y DESARROLLO] -->`.
   - Esto incluye la tarjeta colapsable (`#collapseLoginPruebas`), el formulario oculto (`#formLoginPruebas`) y la función javascript `iniciarSesionPrueba()`.

2. **En el archivo de pruebas alternativo (`login_pruebas.html`)**:
   - Este archivo se encuentra en la raíz del proyecto y debe ser borrado por completo en producción.

---

## 👥 Usuarios de Prueba y Roles Disponibles

A continuación se listan las cuentas de correo utilizadas en el entorno de desarrollo y pruebas para cada rol:

| Rol | Usuario de Prueba | Email de Desarrollo |
| :--- | :--- | :--- |
| **Administrador** | Administrador del Sistema | `luzmariagaraigarai@gmail.com` |
| **Vinculación Académica** | Secretario Académico / VA | `esstefaniamendez@gmail.com` |
| **Director de Escuela** | Director de Escuela | `luzgarai40@gmail.com` |
| **Depto. Cs. Naturales y Exactas** | Director Departamento CNE | `estiloperladoaccesorios@gmail.com` |
| **Depto. Ciencias Sociales** | Director Departamento CS | `garaiestefi@gmail.com` |
| **Profesor (Sandra Casas)** | Docente Scasas (Materias `0174`, `1108`, `1649`) | `accesoriosperlados@gmail.com` |
| **Profesor (Albert Sofia)** | Docente Asofia (Materias `1668`, `1662`) | `esstefaniamendez+profesor@gmail.com` |

---

## 🚀 Ejecución de Pruebas Automatizadas (Playwright)

El proyecto cuenta con una suite de pruebas de integración de punta a punta (E2E) con Playwright ubicada en la carpeta `/pruebas-playwright`.

### Requisitos previos:
1. Tener Node.js instalado.
2. Servidor Apache y MySQL corriendo en XAMPP local.

### Pasos para ejecutar los tests:
1. Abra una terminal en el directorio `pruebas-playwright`:
   ```bash
   cd pruebas-playwright
   ```
2. Instale las dependencias si es la primera vez:
   ```bash
   npm install
   ```
3. Ejecute las pruebas:
   ```bash
   npx playwright test
   ```

La suite ejecutará los 3 flujos de pruebas principales:
- **Circuito Estándar** (Docente $\rightarrow$ Escuela $\rightarrow$ VA $\rightarrow$ Departamento $\rightarrow$ VA Firma Final).
- **Circuito Institucional** (Docente $\rightarrow$ VA $\rightarrow$ Departamento $\rightarrow$ VA Firma Final).
- **Flujo de Rechazo y Re-presentación** (Escuela rechaza con observaciones $\rightarrow$ Docente corrige y vuelve a enviar).
