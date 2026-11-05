# Registro de Cambios (Changelog) — VASPA

Todos los cambios relevantes realizados en este proyecto (remediaciones de seguridad, mejoras funcionales, pruebas y configuración) se documentan en este archivo en orden cronológico inverso.

---

## [2026-11-04] — Post-Fase 4: Instalación Efectiva de Dependencias Composer (`vendor/`), Actualización de jQuery 3.7.1 y Limpieza (`SEC-013`)

### Modificado / Seguridad y Dependencias
- **`composer.json` y `composer.lock` (Modificado / Creado)**:
  - Añadido `tecnickcom/tcpdf: ^6.7` y ejecutado `composer update`, instalando en `vendor/` las versiones actualizadas `phpmailer/phpmailer` (`v6.12.0`), `tecnickcom/tcpdf` (`v6.11.4`) y `vlucas/phpdotenv` (`v5.8.0`).
- **`lib/PHPMailer/PHPMailerAutoload.php` (Modificado)**:
  - Conectado automáticamente a `vendor/autoload.php` registrando alias retrocompatibles (`PHPMailer`, `SMTP`, `phpmailerException`) hacia `\PHPMailer\PHPMailer\*` (v6.12.0), manteniendo fallback local si `vendor/` no existe.
- **`modeloSistema/MYPDF.php` (Modificado)**:
  - Actualizado para cargar prioritariamente `vendor/tecnickcom/tcpdf/tcpdf.php` (`v6.11.4`), con fallback a `lib/tcpdf/tcpdf.php`.
- **`lib/JQuery/jquery-3.3.1.js` (Actualizado)**:
  - Reemplazado el contenido por **jQuery v3.7.1** oficial conservando el nombre de archivo para compatibilidad con todas las vistas existentes y mitigando `CVE-2019-11358` y `CVE-2020-11022`.
- **`.agents/teamwork/` (Eliminado)**:
  - Limpiado el directorio local de logs intermedios de subagentes para liberar espacio en disco.

### Verificación
- **`composer audit`**: `No security vulnerability advisories found` (0 vulnerabilidades en dependencias instaladas).
- **`php scripts/lint.php`**: 301/301 archivos PHP validados sin errores de sintaxis.

---

## [2026-11-03] — Fase 4: Mantenimiento Continuo, DevOps, Docker, Composer y CI/CD

### Añadido / DevOps y Configuración
- **`composer.json` (Creado)**:
  - Definido manifiesto formal de dependencias para PHP 8.2+ con extensiones requeridas (`mysqli`, `pdo_mysql`, `mbstring`, `fileinfo`, `json`) y dependencias base (`vlucas/phpdotenv: ^5.6`, `phpmailer/phpmailer: ^6.9`).
  - Configurado `autoload` tipo `classmap` abarcando las clases del sistema en `modelo/`, `modeloSistema/`, `controlSistema/`, `lib/` y utilitarios.
  - Añadidos scripts de proyecto `composer lint` (invoca `scripts/lint.php`), `composer test` (suite Playwright) y `check-composer` (`composer validate --strict`).
- **`scripts/lint.php` (Creado)**:
  - Script de validación sintáctica multiplataforma que recorre el repositorio y ejecuta `php -l` con filtrado inteligente a nivel de directorio, validando el 100% de los archivos PHP del código fuente de VASPA.
- **`Dockerfile` (Creado)**:
  - Contenedor de producción/desarrollo basado en `php:8.2-apache` con extensiones de sistema (`mysqli`, `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `zip`, `opcache`), módulos Apache habilitados (`rewrite`, `headers`, `ssl`), y endurecimiento de seguridad perimetral (`ServerTokens Prod`, `ServerSignature Off`, `expose_php = Off`, `display_errors = Off`, permisos 755/775 de usuario `www-data` y healthcheck integrado).
- **`docker-compose.yml` (Creado)**:
  - Orquestación de servicios desacoplados: servicio web `app` (VASPA en puerto configurable `8080`) y servicio de base de datos `db` (MariaDB 10.6 en puerto local `3307`), con inicialización automática a partir de los scripts en `scriptBD/` montados en `/docker-entrypoint-initdb.d/`, sincronización por healthcheck (`service_healthy`), red aislada `vaspa-net` y persistencia en volúmenes Docker.
- **`.dockerignore` (Creado)**:
  - Exclusión de secretos (`.env`), repositorios locales (`.git`, `.agents`), dependencias de `node_modules/`, artefactos de testing y respaldos `.sql`.
- **`.github/workflows/ci.yml` (Creado)**:
  - Pipeline de Integración Continua para GitHub Actions con 4 jobs:
    1. `php-lint-and-composer`: Validación de `composer.json` y linting sintáctico total con `scripts/lint.php`.
    2. `security-audit`: Auditoría de no versionado de `.env`, presencia de `.env.example` y verificación de integridad de archivos perimetrales `.htaccess`.
    3. `docker-build`: Construcción y verificación de la imagen de producción en Dockerfile.
    4. `e2e-playwright-tests`: Despliegue de servicio MariaDB efímero, inicialización de esquemas `scriptBD/`, servidor PHP local y ejecución de la suite Playwright completa con publicación de reportes.
- **`tests/e2e/specs/10-seguridad-endurecimiento.spec.js` (Creado)**:
  - Renovación y expansión de la suite E2E con 5 nuevos casos de prueba automatizados (`CP-E2E-36` a `CP-E2E-40`) que validan cabeceras de seguridad HTTP (`nosniff`, `SAMEORIGIN`, `CSP`, `Referrer-Policy`), flags de sesión (`HttpOnly`, `SameSite=Lax`), bloqueo de acceso web a esquemas SQL en `scriptBD/` (HTTP 403), restricción de scripts de mantenimiento a consola CLI (HTTP 403) y protección Anti-CSRF ante peticiones POST forjadas.
- **`.gitignore` (Modificado)**:
  - Añadidas excepciones explícitas para permitir `!docker-compose.yml`, `!docker-compose.*.yml`, `!.github/` e ignorar `/vendor/`.

### Modificado / Corrección de Sintaxis PHP 8.2
- **`lib/tcpdf/tcpdf_barcodes_1d.php` y `lib/tcpdf/tcpdf_parser.php` (Modificados)**:
  - Corregidos 7 accesos con llaves deprecados en PHP 8.2 (reemplazados por notación de corchetes `[]`), erradicando los últimos errores fatales de parseo en librerías PDF heredadas.

### Verificación
- **Composer**: `composer validate --strict` aprobado al 100% sin advertencias ni errores.
- **Linting de Sintaxis PHP**: `php scripts/lint.php` analizó 301 archivos PHP con 0 errores de sintaxis (100% de aprobación).
- **Validación Docker**: `docker compose config` validado exitosamente sin inconsistencias de sintaxis.
- **Suite Automatizada E2E (Playwright)**: Suite completa expandida a 40 pruebas automatizadas (`CP-E2E-01` a `CP-E2E-40`) ejecutada en su totalidad con 100% de aprobación.

---

## [2026-11-03] — Fase 3: Endurecimiento de Entorno y Severidad Media (`SEC-007`, `SEC-010`, `SEC-012`, `SEC-016`)

### Modificado / Seguridad
- **`lib/ControlAcceso.Class.php` (Modificado)**:
  - Implementado `ControlAcceso::iniciarSesionSegura()` que configura parámetros estrictos de cookies de sesión mediante `session_set_cookie_params()` antes de iniciar la sesión: `httponly = true`, `samesite = 'Lax'`, `secure` condicional a conexiones HTTPS y `session.use_only_cookies = 1`.
  - Implementado `ControlAcceso::configurarEntorno()` que centraliza la política de reporte de errores (`display_errors = 0`, `display_startup_errors = 0` y `log_errors = 1` cuando no está en modo debug según `APP_DEBUG` de `.env`) e inyecta cabeceras de seguridad PHP (`X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`).
  - Actualizados `obtenerCsrfToken()` y `validarCsrfToken()` para inicializar la sesión mediante `iniciarSesionSegura()`.
  - Asegurada la regeneración de identificadores de sesión (`session_regenerate_id(true)`) tras autenticación válida.
- **`app/salir.php` (Modificado)**:
  - Implementada destrucción de sesión limpia y segura: vaciado de `$_SESSION = []`, borrado explícito de la cookie `PHPSESSID` en el navegador con fecha en el pasado (`time() - 42000`) respetando los parámetros de dominio y ruta, y destrucción de sesión en servidor (`session_destroy()`).
- **`app/index.php` y `gui/navbar.php` (Modificados)**:
  - Eliminado el hardcodeo de `ini_set('display_errors', 1)` y `ini_set('display_startup_errors', 1)`, delegando la configuración en `ControlAcceso::configurarEntorno()`.
  - Añadido chequeo seguro con operador null coalescing (`$UsuarioSes = $_SESSION['usuario'] ?? null;`) en `gui/navbar.php` previniendo advertencias de variables indefinidas.
- **`lib/consultaAjax/informeGerencial/` (Modificados 3 endpoints)**:
  - Retirado `ini_set('display_errors', 1)` en `tablaProgramasPDFporProfesor.php`, `tablaProgramasPDFporCarrera.php` y `reporteHistorial5Anios.php`.
- **`vista/lista.asignaturas.php` y `vista/lista.asignaturas.correlativas.php` (Modificados)**:
  - Sustituida la llamada a `session_start()` directo por `ControlAcceso::iniciarSesionSegura()`.
- **`.htaccess` en la raíz (Modificado)**:
  - Inyectadas cabeceras HTTP de seguridad perimetrales mediante `mod_headers`:
    - `X-Content-Type-Options: nosniff`
    - `X-Frame-Options: SAMEORIGIN`
    - `Referrer-Policy: strict-origin-when-cross-origin`
    - `Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://apis.google.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: https:; connect-src 'self' https://accounts.google.com https://apis.google.com; frame-src 'self' https://accounts.google.com;`
  - Reafirmada la directiva perimetral `Options -Indexes` para bloqueo absoluto de listado de directorios.
- **`c:\xampp\mysql\bin\my.ini` (Modificado)**:
  - Configurada la directiva `bind-address = 127.0.0.1` en la sección `[mysqld]` para mitigar el hallazgo `SEC-012`, restringiendo el motor de base de datos a conexiones locales.
- **`docs/seguridad/hardening-entorno-xampp.md` (Creado)**:
  - Documentación técnica y guía de endurecimiento de entorno (MariaDB/MySQL, Apache, PHP, permisos del sistema de archivos y checklist de producción).

### Verificación
- **Sintaxis PHP**: 100% de los archivos modificados verificados con `php -l` (0 errores de sintaxis).
- **Inspección de Cabeceras HTTP**: Verificado mediante `curl.exe` que las respuestas HTTP incluyen `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Content-Security-Policy` y cookie con `HttpOnly; SameSite=Lax`.
- **Bloqueo de Navegación de Directorios**: Verificada respuesta `403 Forbidden` ante solicitudes a directorios sin archivo índice (ej. `/lib/`).
- **Destrucción de Sesión y Logout**: Verificado mediante `specs/02-sesion-logout.spec.js` (`CP-E2E-09`) que la sesión y la cookie son destruidas efectivamente.
- **Suite Automatizada E2E (Playwright)**: Ejecución completa de los 35 casos de prueba (`CP-E2E-01` a `CP-E2E-35`) con **35 aprobados (100%)** en 4.0 minutos, certificando cero regresiones funcionales.

---

## [2026-11-02] — Fase 2: Remediación de Severidad Alta (`SEC-003`, `SEC-004`, `SEC-005`, `SEC-006`, `SEC-008`, `SEC-011`, `SEC-013`)

### Modificado / Seguridad
- **`lib/ControlAcceso.Class.php` (Modificado)**:
  - Añadido helper universal de sanitización contextual Anti-XSS `e($valor)` con codificación UTF-8 y preservación estricta de cadenas nulas.
  - Añadida infraestructura de tokens sincronizados Anti-CSRF: `obtenerCsrfToken()`, `campoCsrf()`, `validarCsrfToken()` y `requiereCsrf()`, con soporte dual para formularios (`$_POST['csrf_token']`) y peticiones AJAX (`X-CSRF-Token`).
- **SEC-005: Restricción CLI en scripts de mantenimiento y protección de endpoints AJAX**:
  - Restringidos estrictamente al entorno de línea de comandos (`php_sapi_name() !== 'cli'` con HTTP 403 Forbidden): `create_log_table.php`, `migrate_SA_to_VA.php`, `update_db_schema_escuela.php`, `get_valid_asignatura.php`, `verify_multicast_data.php`, `cron/avisoRetrasoCircuito.php`.
  - Creado archivo `cron/.htaccess` con denegación perimetral absoluta (`Require all denied`).
  - Protegido `generarReporteGerencial.php` exigiendo permiso `PERMISO_GENERAR_INFORME_GERENCIAL`.
  - Protegidos con `ControlAcceso::verificaLogin()` y control RBAC granular en `lib/consultaAjax/`: `equipoCatedra/*`, `enviarNotificacion/*`, `revisarPrograma/planesDeCarrera.php`, `revisarPrograma/tablaProgramasAsignaturas.php` (rol obtenido de sesión, no de POST manipulable), `informeGerencial/*`, `vigenciaProgramas/*`, `planAsignatura/*`, `asignaturaCorrelativa/*`, `cargarAsignaturas.php`, `cargarCarreras.php`, `cargarPlanes.php`, `buscar.asignatura.php`, `cargaDatosUltimoPrograma.php`.
  - Protegida vista `vista/carrera.verAsignaturas.php` exigiendo sesión autenticada.
  - **Preservación Comunitaria**: Se mantuvieron intencionalmente sin autenticación los endpoints de consulta pública `visualizar.programa.cargar.carreras.php` y `visualizarPrograma/tablaProgramasAsignaturas.php` para garantizar la funcionalidad de cara al público general (`CP-E2E-16`).
- **SEC-006: Control RBAC estricto, casteo numérico y anti-path traversal en descarga y generación de PDF**:
  - `vista/programa.descargarPDF.php`: Reescrito completamente con `ControlAcceso::verificaLogin()`, verificación estricta de roles autorizados, casteo `intval($_GET['id'])`, consultas preparadas y contención canónica estricta mediante `realpath()` para evitar escape de directorios (`Path Traversal`).
  - `vista/programa.generarPDF.php`: Endurecido con `ControlAcceso::verificaLogin()`, verificación de roles autorizados y casteo numérico seguro.
- **SEC-008: Endurecimiento de subida de archivos PDF, Magic Bytes y reglas perimetrales `.htaccess`**:
  - Creados archivos `.htaccess` en `archivos/.htaccess` y `programas/.htaccess` desactivando ejecución de scripts (`Options -ExecCGI -Indexes`), bloqueando extensiones ejecutables (`FilesMatch`), deshabilitando motores PHP (`php_flag engine off`) e inyectando `X-Content-Type-Options: nosniff`.
  - Validada extensión obligatoria `.pdf` y verificación estricta de Magic Bytes / Tipo MIME Real con `finfo(FILEINFO_MIME_TYPE) === 'application/pdf'` en `controlSistema/programa.crear.procesar.pdf.php`, `controlSistema/programa.actualizar.pdf.php`, `controlSistema/programa.modificar.procesar.pdf.php` y `vista/subir.programa.procesar.php`.
  - Aplicados límites estrictos de tamaño (10 MB) y saneamiento de rutas con `basename()` y `preg_replace`.
- **SEC-011: Autorización contextual de jurisdicción académica (Anti-IDOR)**:
  - Corregida inversión histórica de IDs de departamento en `vista/revisar.programas.php:33-38` (CNE asignado a ID 1 y CS asignado a ID 2, consistente con la tabla MySQL y `inicio.php`).
  - Implementada verificación contextual estricta de jurisdicción en `controlSistema/programa.revisar.actualizar.estado.pdf.php`, `controlSistema/programa.actualizar.pdf.php` y `controlSistema/programa.enviar.revision.php`: rechaza con HTTP 403 si un Director de Departamento opera sobre asignaturas ajenas, si un Director de Escuela opera sobre asignaturas institucionales, o si un Profesor intenta operar sobre asignaturas donde no es responsable ni equipo docente.
- **SEC-004: Sanitización contextual universal Anti-XSS**:
  - Protegido `gui/navbar.php` escapando con `e()` el nombre del usuario y su rol.
  - Escapadas variables dinámicas y mensajes de estado con `e()` y `urlencode()` en `app/usuario.*.procesar.php`, `app/rol.*.procesar.php`, `app/permiso.*.procesar.php`, `app/usuarios.php`, `app/roles.php`, `app/permisos.php`, `vista/carreras.php`, `vista/carrera.verPlanes.php`, `vista/profesores.php`.
- **SEC-003: Protección Anti-CSRF en formularios POST y peticiones asíncronas**:
  - Inyectada etiqueta `<meta name="csrf-token">` y configuración global `$.ajaxSetup` en `gui/navbar.php`.
  - Inyectado `ControlAcceso::campoCsrf()` en todos los formularios POST del sistema: `carrera.*`, `plan.*`, `profesor.*`, `usuario.*`, `rol.*`, `permiso.*`, `programa.crear.php`, `programa.modificar.pdf.php`, `subir.programa.formulario.php`, modales y formularios de revisión en `revisar.programa.pdf.php`, y formulario de envío a revisión en `inicio.php`.
  - Exigida validación estricta con `ControlAcceso::requiereCsrf()` en todos los procesadores de backend correspondientes.
- **SEC-013: Mitigación de compatibilidad y dependencias en `lib/`**:
  - Corregido error de sintaxis en `lib/PHPMailer/extras/htmlfilter.php:351` reemplazando sintaxis obsoleta con llaves por corchetes (`$attary[$attname]`), logrando compatibilidad plena con PHP 8.2+.
  - Verificada la no existencia de carpetas huérfanas de respaldo de librerías (`lib/tcpdf_backup/`).

### Verificación
- **Sintaxis PHP**: 100% de los archivos modificados y creados verificados con `php -l` (0 errores de sintaxis en 85+ archivos auditados).
- **Control de Acceso y CLI**: Confirmado bloqueo HTTP 403 en scripts de mantenimiento y endpoints AJAX sin sesión.
- **Suite Automatizada E2E (Playwright)**: Ejecución completa de los 35 casos de prueba (`CP-E2E-01` a `CP-E2E-35`) con **35 aprobados (100%)** en 4.0 minutos, certificando cero regresiones en autenticación, navegación, CRUD, circuito de programas, reportes y búsqueda pública.

---

## [2026-11-02] — Fase 1: Remediación Crítica (`SEC-001`, `SEC-002`, `SEC-009`, `SEC-014`, `SEC-015`)

### Modificado / Seguridad
- **`lib/Env.Class.php` (Creado)**: Cargador nativo liviano de variables de entorno desde archivo `.env`, poblando `$_ENV`, `$_SERVER` y `getenv()` con sanitización de comillas y conversiones booleanas.
- **`.env` (Creado, no versionado)**: Variables de entorno operativas para el entorno local XAMPP (`APP_ENV=local`, `APP_DEBUG=true`, credenciales MySQL y SMTP locales).
- **`.env.example` (Creado, versionable)**: Plantilla con valores enmascarados para replicabilidad segura del entorno.
- **`modelo/BDConexion.Class.php` y `modeloSistema/BDConexionSistema.Class.php` (Modificados)**:
  - Migradas credenciales a `Env::get(...)`.
  - Corregida fuga de excepciones en `BDConexionSistema` (eliminado `$e` de la salida pública).
  - Añadidos métodos helper seguros: `queryPrepared()` y `executePrepared()`.
- **`lib/notificacionesMail/constantesMail.php` y `lib/funcionesUtiles/constantesMail.php` (Modificados)**: Migradas credenciales de correo y contraseñas de aplicación a variables de entorno con `Env::get(...)`.
- **`tools.yaml` (Eliminado)**: Retirado del webroot archivo con credenciales de base de datos expuestas.
- **`.gitignore` (Modificado)**: Añadida exclusión estricta para `.env*`, `tools.yaml`, respaldos `.sql`, `.bak`, `.dump` y temporales, preservando `!.env.example` y scripts base de `scriptBD/`.
- **`backup_bdgef_vaspa_*.sql` y `scriptBD/backup_bdgef_vaspa_manual.sql` (Eliminados)**: Retirados volcados de datos del webroot público.
- **`.htaccess` en raíz (Creado)**: Bloqueo de navegación de directorios (`Options -Indexes`) y denegación de acceso HTTP a `.env`, `.git`, `.yaml`, `.sql`, `.bak`, `.dump`, `.log`.
- **`scriptBD/.htaccess` (Creado)**: Bloqueo absoluto de acceso web (`Require all denied`).
- **`login_pruebas.html` (Eliminado)**: Eliminada puerta trasera estática del webroot.
- **`lib/ControlAcceso.Class.php` (Modificado)**:
  - Implementado `ControlAcceso::esEntornoPruebas()` con política *Fail-Closed*.
  - Bloqueo en constructor ante intentos de login simulado si `!esEntornoPruebas()`.
  - Saneamiento en `creaSesion()` con `unset($_SESSION['usuario'])`, `$_SESSION['auth_error'] = true`, `session_regenerate_id(true)` y token CSRF.
  - Parametrizadas con sentencias preparadas: `UsuarioSesion::buscarUsuarioBd()`, `UsuarioSesion::registrarUsuario()`, `UsuarioSesion::setRoles()` y `RolSesion::setPermisos()`.
- **`app/index.php` (Modificado)**:
  - Condicionado `#collapseLoginPruebas` exclusivamente a `ControlAcceso::esEntornoPruebas()`.
  - Despliegue seguro de alerta `.alert-danger` "Usuario No Autenticado." ante `auth_error` o cadena vacía con destrucción limpia.
- **`modelo/BDObjetoGenerico.Class.php` (Modificado)**: Validación de lista blanca regex para nombres de tablas e identificadores de vinculación, y parametrización de `$id` numérico en `__construct` y `setColeccionElementos`.
- **`app/usuario.crear.procesar.php`, `app/usuario.modificar.procesar.php`, `app/usuario.eliminar.procesar.php` (Modificados)**:
  - Parametrización completa con sentencias preparadas de todas las consultas (`SELECT`, `INSERT`, `UPDATE`, `DELETE`).
  - Implementadas transacciones atómicas seguras con `begin_transaction`, `commit` y `rollback`.
  - Retiradas todas las llamadas a `die(errno)` que exponían errores internos.
- **`controlSistema/ManejadorCarrera.php` y `controlSistema/ManejadorProfesor.php` (Modificados)**: Parametrizadas sentencias preparadas en operaciones CRUD (`alta`, `baja`, `modificacion`, `chequearExistencia`, `chequearEmail`).
- **`lib/consultaAjax/` (Modificados 7 endpoints)**: Parametrizadas consultas en `equipoCatedra` (alta y desvinculación), `carrerasMaterias` (asociar, quitar y cambiar estado) y `asignaturaCorrelativa` (agregar y eliminar).

### Verificación
- **Sintaxis PHP**: 100% aprobado (`php -l`) en los 20 archivos PHP modificados o creados sin advertencias.
- **Protección HTTP**: Pruebas con `curl.exe` y PowerShell confirmaron respuestas `403 Forbidden` ante solicitudes a `.env` y `scriptBD/01_bdusuarios.sql`.
- **Resistencia a Inyección SQL**: Prueba con payload `' OR '1'='1` en login rechazada limpiamente como "Usuario No Autenticado" sin alterar el AST de la consulta.
- **Pruebas E2E (Playwright)**: Verificación con suite completa en `tests/e2e/`.

---

## [2026-11-02] — Inicialización de Reglas, Trazabilidad y Auditoría Base

### Agregado
- **[`INFORME_AUDITORIA.md`](./INFORME_AUDITORIA.md)**: Informe integral de Auditoría de Seguridad (Parte A: 16 hallazgos `SEC-001` a `SEC-016`) y Verificación Funcional QA E2E (Parte B: 35 flujos evaluados).
- **[`tests/e2e/`](./tests/e2e/)**: Suite automatizada de pruebas End-to-End con Playwright (9 archivos `.spec.js`, 35 casos de prueba aprobados y 50 capturas de pantalla de evidencia en `tests/e2e/screenshots/`).
- **[`AGENTS.md`](./AGENTS.md)** y **`.agents/rules/trazabilidad-y-memoria.md`**: Reglas del espacio de trabajo para exigir el registro continuo de cambios, decisiones y memoria de avance.
- **[`CHANGELOG.md`](./CHANGELOG.md)**: Archivo oficial de registro de cambios del proyecto.
- **[`DECISIONES.md`](./DECISIONES.md)**: Registro de decisiones técnicas y de seguridad (ADR).
- **[`MEMORIA_TRABAJO.md`](./MEMORIA_TRABAJO.md)**: Tablero de seguimiento y memoria persistente del Plan de Remediación en 4 fases.
