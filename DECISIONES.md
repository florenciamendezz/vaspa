# Registro de Decisiones Técnicas y de Seguridad (DECISIONES.md)

Este documento registra las decisiones arquitectónicas, de seguridad y de calidad (formato ADR — *Architecture Decision Record*) tomadas durante la auditoría y remediación del sistema **VASPA**.

---

## ADR-001: Ejecución de Auditoría Inicial en Modo No Destructivo y Suite E2E Aislada
- **Fecha:** 2026-10-29
- **Estado:** Implementada
- **Contexto:** Se requería auditar la seguridad del código y entorno de VASPA y verificar funcionalmente todos sus flujos sin alterar el código fuente existente ni contaminar los datos académicos reales de la base de datos local `bdgef_vaspa`.
- **Decisión:**
  1. Ejecutar el relevamiento estático (OWASP Top 10) y de infraestructura sin modificar ningún archivo original del repositorio.
  2. Construir la suite automatizada con Playwright en un directorio independiente (`tests/e2e/`) utilizando prefijos aislados (`TEST_E2E_*`, carrera `990`) con rutinas de limpieza automática post-ejecución.
- **Consecuencias:** Se obtuvo una línea base 100% verificable (35/35 tests E2E pasando) que ahora sirve como red de seguridad contra regresiones mientras se aplican las correcciones de código.

---

## ADR-002: Sistema de Trazabilidad Continua (`CHANGELOG.md`, `DECISIONES.md`, `MEMORIA_TRABAJO.md`)
- **Fecha:** 2026-11-02
- **Estado:** Implementada
- **Contexto:** Al iniciar la ejecución del Plan de Remediación (4 fases, 16 hallazgos de seguridad), es indispensable mantener trazabilidad exacta de cada archivo modificado, el motivo de cada decisión técnica y el estado de avance entre sesiones.
- **Decisión:** Establecer reglas obligatorias en [`AGENTS.md`](./AGENTS.md) para que cada modificación se documente en [`CHANGELOG.md`](./CHANGELOG.md), cada definición técnica en [`DECISIONES.md`](./DECISIONES.md) y el progreso paso a paso en [`MEMORIA_TRABAJO.md`](./MEMORIA_TRABAJO.md).
- **Consecuencias:** Cualquier desarrollador o agente puede retomar el trabajo en cualquier punto con contexto completo y auditable.

---

## ADR-003: Carga Centralizada de Variables de Entorno con `Env.Class.php` y Plantilla `.env.example`
- **Fecha:** 2026-11-02
- **Estado:** Implementada
- **Hallazgos:** `SEC-009`, `SEC-015`
- **Contexto:** Las credenciales de MySQL y cuentas de correo SMTP institucionales se encontraban expuestas en texto plano dentro del código versionado (`BDConexion.Class.php`, `BDConexionSistema.Class.php`, `constantesMail.php`) y en un archivo no protegido `tools.yaml` en la raíz del webroot.
- **Decisión:**
  1. Implementar la clase nativa `lib/Env.Class.php` sin dependencias externas (previo a la adopción formal de Composer en Fase 4), con lectura segura de archivos `.env`, sanitización de comillas, tipado booleano y soporte para `$_ENV`, `$_SERVER` y `getenv()`.
  2. Proveer plantilla pública `.env.example` con valores enmascarados y generar `.env` local no versionado.
  3. Desacoplar las conexiones a bases de datos (`BDConexion`, `BDConexionSistema`) y notificaciones por correo para consumir exclusivamente `Env::get(...)`.
  4. Eliminar `tools.yaml` y actualizar `.gitignore` para bloquear taxativamente `.env*` y `tools.yaml`.
- **Consecuencias:** Se eliminaron todos los secretos en texto plano del repositorio versionable y se garantiza la portabilidad entre desarrollo, testing y producción sin exponer credenciales.

---

## ADR-004: Blindaje Perimetral Web con `.htaccess` y Aislamiento de Scripts SQL
- **Fecha:** 2026-11-02
- **Estado:** Implementada
- **Hallazgos:** `SEC-014`, `SEC-015`
- **Contexto:** Volcados de base de datos `.sql` se encontraban expuestos en el webroot accesible mediante HTTP directo, careciendo de archivos `.htaccess` protectores y permitiendo la navegación de directorios (Directory Browsing).
- **Decisión:**
  1. Eliminar de inmediato los volcados sueltos en raíz (`backup_bdgef_vaspa_*.sql`) y aislar `scriptBD/backup_bdgef_vaspa_manual.sql`.
  2. Crear `.htaccess` en la raíz del proyecto desactivando el listado navegable (`Options -Indexes`) y denegando con HTTP 403 Forbidden cualquier acceso a archivos `.env`, `.git`, `.yaml`, `.sql`, `.bak`, `.dump` y `.log`.
  3. Crear `.htaccess` en el directorio `scriptBD/` con regla absoluta `Require all denied` (Apache 2.4+) y compatibilidad con Apache 2.2.
- **Consecuencias:** Todo intento de descarga externa de esquemas o respaldos SQL retorna código de estado 403 Forbidden.

---

## ADR-005: Restricción del Mecanismo de Pruebas a Entorno Local y Saneamiento de Sesiones
- **Fecha:** 2026-11-02
- **Estado:** Implementada
- **Hallazgo:** `SEC-002`
- **Contexto:** La aplicación poseía una puerta trasera estática (`login_pruebas.html`), un acordeón de pruebas expuesto (`collapseLoginPruebas`) y un backend (`ControlAcceso.Class.php`) que admitía cualquier POST con `email` sin contraseña ni verificación, asignando `$_SESSION['usuario'] = ""` cuando el usuario no existía (origen de `SEC-006`).
- **Decisión:**
  1. Eliminar `login_pruebas.html` del webroot.
  2. Implementar `ControlAcceso::esEntornoPruebas()` con política *Fail-Closed*: solo si `APP_ENV === 'local'` o `APP_ENV === 'testing'` se admite la simulación.
  3. En `ControlAcceso::__construct()`, bloquear peticiones de prueba en entornos de producción con HTTP 403 y log de auditoría.
  4. En `creaSesion()`, regenerar el identificador con `session_regenerate_id(true)`, generar token CSRF inicial, y ante usuarios no registrados usar `unset($_SESSION['usuario'])` y fijar bandera transitoria `$_SESSION['auth_error'] = true`.
  5. En `app/index.php`, condicionar la renderización de `#collapseLoginPruebas` a `ControlAcceso::esEntornoPruebas()`.
- **Consecuencias:** Se anula la explotación del bypass en entornos remotos o de producción, manteniendo la capacidad de desarrollo y asegurando el funcionamiento sin regresión de los tests Playwright E2E.

---

## ADR-006: Parametrización Sistemática con Sentencias Preparadas (`mysqli_stmt`) y Helpers en Capa de Datos
- **Fecha:** 2026-11-02
- **Estado:** Implementada
- **Hallazgo:** `SEC-001`
- **Contexto:** Múltiples puntos críticos de la aplicación concatenaban directamente cadenas provenientes de `$_POST` en sentencias SQL, permitiendo inyección SQL tanto en autenticación (`' OR '1'='1`) como en ABM de usuarios, carreras, profesores y endpoints AJAX.
- **Decisión:**
  1. Incorporar en `BDConexion` y `BDConexionSistema` los métodos helpers `queryPrepared` y `executePrepared`.
  2. Parametrizar con `prepare()` y `bind_param()` todas las consultas de `ControlAcceso` (`buscarUsuarioBd`, `registrarUsuario`, `setPermisos`, `setRoles`).
  3. Refactorizar `BDObjetoGenerico` validando nombres de tabla mediante listas blancas regex y parametrizando `$id` como entero (`"i"`).
  4. Parametrizar las operaciones de `usuario.crear.procesar.php`, `usuario.modificar.procesar.php` y `usuario.eliminar.procesar.php`, implementando transacciones atómicas seguras y eliminando llamadas a `die(errno)`.
  5. Parametrizar métodos de `ManejadorCarrera` y `ManejadorProfesor`, y los 7 endpoints AJAX de `lib/consultaAjax/`.
- **Consecuencias:** Se erradica la vulnerabilidad de inyección SQL en todos los flujos de la Fase 1, manteniendo 100% de compatibilidad con las especificaciones y flujos académicos de VASPA.

---

## ADR-007: Restricción CLI en Scripts de Mantenimiento y Protección Integral de Endpoints AJAX
- **Fecha:** 2026-11-02
- **Estado:** Implementada
- **Hallazgo:** `SEC-005`
- **Contexto:** Scripts DDL y de migración estructural (`create_log_table.php`, `migrate_SA_to_VA.php`, `update_db_schema_escuela.php`, `verify_multicast_data.php`, `cron/avisoRetrasoCircuito.php`) residían en el webroot público sin autenticación, exponiendo el esquema de base de datos a ejecuciones externas arbitrarias. Asimismo, múltiples endpoints en `lib/consultaAjax/` permitían consultas y modificaciones de estado sin verificar sesión ni permisos de rol.
- **Decisión:**
  1. Restringir la ejecución de scripts administrativos y tareas cron exclusivamente al modo consola (`CLI`) mediante `php_sapi_name() !== 'cli'` retornando HTTP 403 Forbidden ante cualquier acceso vía navegador o servidor web.
  2. Crear `cron/.htaccess` con directiva absoluta `Require all denied`.
  3. Proteger endpoints de `lib/consultaAjax/` exigiendo `ControlAcceso::verificaLogin()` y permisos de rol específicos. En `revisarPrograma/tablaProgramasAsignaturas.php`, derivar el rol directamente de la sesión activa (`$_SESSION['usuario']`) en lugar de confiar en el parámetro `$_POST['rol']` manipulable por el cliente.
  4. Exigir `ControlAcceso::requierePermiso(PermisosSistema::PERMISO_GENERAR_INFORME_GERENCIAL)` en `generarReporteGerencial.php`.
  5. Preservar intencionalmente sin autenticación los endpoints `visualizar.programa.cargar.carreras.php` y `visualizarPrograma/tablaProgramasAsignaturas.php` para mantener operativa la búsqueda comunitaria pública de programas con triple aprobación (`CP-E2E-16`).
- **Consecuencias:** Los scripts de infraestructura quedan aislados de Internet y los servicios AJAX quedan protegidos contra accesos no autorizados, sin romper la consulta pública reglamentaria.

---

## ADR-008: Descarga y Generación de PDF con RBAC Estricto, Consultas Preparadas y Contención Canónica Anti-Traversal
- **Fecha:** 2026-11-02
- **Estado:** Implementada
- **Hallazgo:** `SEC-006`
- **Contexto:** `vista/programa.descargarPDF.php` y `vista/programa.generarPDF.php` permitían la descarga de programas evaluando un parámetro GET sin comprobar si la sesión de usuario era válida, admitiendo casteo suelto y concatenación directa de rutas de archivos en disco, lo que posibilitaba la enumeración no autorizada de programas analíticos y potenciales ataques de Directory Traversal.
- **Decisión:**
  1. Requerir obligatoriamente `ControlAcceso::verificaLogin()` al inicio de ambos scripts.
  2. Implementar control de acceso basado en roles (RBAC) restringiendo el acceso exclusivamente a roles legítimos: Administrador, Vinculación Académica, Director de Escuela, Secretario de Escuela, Director de Departamento y Profesor.
  3. Sanitizar y tipificar el identificador con `intval($_GET['id'])` y parametrizar la consulta a la base de datos con sentencias preparadas (`bind_param`).
  4. En `programa.descargarPDF.php`, resolver la ruta canónica física del archivo mediante `realpath()` y validar estrictamente que pertenezca al directorio base autorizado (`strpos($rutaReal, $baseDir) === 0`), rechazando con HTTP 403 o 404 cualquier intento de escape de ruta.
- **Consecuencias:** Se erradica el bypass de descarga y la fuga de documentos fuera del circuito académico, garantizando integridad en la entrega de PDFs.

---

## ADR-009: Endurecimiento de Subida de Archivos, Validación Estricta de Magic Bytes y Reglas Perimetrales `.htaccess`
- **Fecha:** 2026-11-02
- **Estado:** Implementada
- **Hallazgo:** `SEC-008`
- **Contexto:** La subida de programas analíticos (`subir.programa.procesar.php` y scripts de `controlSistema/`) dependía del valor reportado por el cliente (`$_FILES['programa']['type']`) y carecía de controles en directorios de almacenamiento (`archivos/` y `programas/`), permitiendo potencialmente la subida de scripts PHP maliciosos disfrazados o ataques de sobrescritura de ficheros del sistema.
- **Decisión:**
  1. Crear archivos `.htaccess` en `archivos/` y `programas/` desactivando la ejecución de scripts (`Options -ExecCGI -Indexes`), bloqueando extensiones ejecutables o de configuración (`FilesMatch`), desactivando el motor PHP (`php_flag engine off`) e inyectando la cabecera defensiva `X-Content-Type-Options: nosniff`.
  2. En todos los procesadores de subida de archivos, exigir autenticación con `ControlAcceso::verificaLogin()`.
  3. Validar la extensión obligatoria `.pdf` (en minúsculas) y comprobar los Magic Bytes reales del archivo en el servidor utilizando `finfo(FILEINFO_MIME_TYPE) === 'application/pdf'`, descartando cualquier archivo cuyo contenido real no sea un documento PDF.
  4. Limitar el tamaño a un máximo de 10 MB y sanitizar los nombres de archivo en disco usando `basename()` y expresiones regulares restrictivas (`preg_replace('/[^a-zA-Z0-9]/', '', ...)`).
- **Consecuencias:** El servidor neutraliza cualquier intento de subida o ejecución de webshells o archivos maliciosos en las carpetas de almacenamiento público.

---

## ADR-010: Control de Acceso Contextual de Jurisdicción Académica (Mitigación Anti-IDOR)
- **Fecha:** 2026-11-02
- **Estado:** Implementada
- **Hallazgo:** `SEC-011`
- **Contexto:** En el circuito secuencial de aprobación de programas, cualquier usuario con rol evaluador podía enviar peticiones POST manipulando el `idPrograma` para aprobar, devolver o modificar programas pertenecientes a otros departamentos, escuelas o cátedras ajenas (Insecure Direct Object Reference). Adicionalmente, existía una discrepancia histórica en `vista/revisar.programas.php:33-38` donde los IDs de los departamentos CNE y CS se encontraban invertidos respecto a la base de datos MySQL.
- **Decisión:**
  1. Corregir la asignación de departamentos en `vista/revisar.programas.php`: asociar `MAIL_DEPTO_CNE` con `idDepartamento = '1'` y `MAIL_DEPTO_CS` con `idDepartamento = '2'`, unificando la lógica con la tabla MySQL y `inicio.php`.
  2. En `controlSistema/programa.revisar.actualizar.estado.pdf.php`, `programa.actualizar.pdf.php` y `programa.enviar.revision.php`, incorporar validación contextual estricta de jurisdicción previa a cualquier mutación de estado:
     - Directores de Departamento: se verifica que el departamento de la asignatura coincida exactamente con el departamento del directivo autenticado.
     - Directores y Secretarios de Escuela: se bloquea cualquier operación sobre asignaturas de carácter institucional (`getEsInstitucional() == 1`).
     - Profesores: se valida contra la base de datos que el usuario pertenezca a la cátedra o sea responsable asignado a la materia.
  3. Denegar con HTTP 403 Forbidden y registrar alertas en los logs de seguridad ante cualquier intento de transgresión de ámbito.
- **Consecuencias:** Se elimina la vulnerabilidad IDOR y se preserva la integridad del flujo académico según las atribuciones reglamentarias de cada autoridad docente.

---

## ADR-011: Sanitización Contextual Universal Anti-XSS mediante Helper `e()`
- **Fecha:** 2026-11-02
- **Estado:** Implementada
- **Hallazgo:** `SEC-004`
- **Contexto:** Diversas variables provenientes de la base de datos, sesiones o parámetros de consulta (`$_SESSION['usuario']->nombre`, mensajes de procesadores en redirecciones `?mensaje=...`, listados de roles y usuarios) se renderizaban en el DOM sin codificación HTML adecuada, exponiendo a los usuarios a ataques de Cross-Site Scripting reflejado y almacenado.
- **Decisión:**
  1. Definir en `lib/ControlAcceso.Class.php` la función helper global `e($valor)` implementando `htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
  2. Refactorizar `gui/navbar.php` para imprimir el nombre y rol del usuario mediante `e(...)`.
  3. Escapar todas las variables dinámicas en tablas y formularios de `app/usuarios.php`, `app/roles.php`, `app/permisos.php`, `vista/carreras.php`, `vista/carrera.verPlanes.php` y `vista/profesores.php`.
  4. En los procesadores que retornan mensajes por parámetro URL o alertas de sesión, sanear los valores salientes con `e()` y codificar parámetros con `urlencode()`.
- **Consecuencias:** Se neutraliza la inyección de código JavaScript o etiquetas HTML no deseadas en el contexto del navegador.

---

## ADR-012: Protección Sincronizada contra Falsificación de Peticiones en Sitios Cruzados (Anti-CSRF)
- **Fecha:** 2026-11-02
- **Estado:** Implementada
- **Hallazgo:** `SEC-003`
- **Contexto:** La totalidad de las operaciones de modificación de estado (creación, edición y baja de usuarios, roles, permisos, carreras, planes, profesores y avance de etapas de programas analíticos) utilizaban formularios POST o llamadas AJAX sin ningún token de validación de procedencia, permitiendo que un atacante engañase al navegador de una víctima autenticada para ejecutar acciones inadvertidas.
- **Decisión:**
  1. Incorporar en `ControlAcceso` la infraestructura criptográficamente segura de Anti-CSRF (`random_bytes(32)` almacenado en `$_SESSION['csrf_token']`), con los métodos `obtenerCsrfToken()`, `campoCsrf()`, `validarCsrfToken()` y `requiereCsrf()`.
  2. En `gui/navbar.php`, exponer el token en una etiqueta `<meta name="csrf-token" content="...">` y enlazarlo globalmente a jQuery mediante `$.ajaxSetup({ headers: { 'X-CSRF-Token': ... } })` para proteger todas las solicitudes asíncronas.
  3. Inyectar `ControlAcceso::campoCsrf()` en todos los formularios POST del sistema:
     - ABM de Carreras (`carrera.crear.php`, `carrera.modificar.php`, `carrera.eliminar.php`).
     - ABM de Planes (`plan.crear.php`, `plan.modificar.php`, `plan.eliminar.php`).
     - ABM de Profesores (`profesor.crear.php`, `profesor.modificar.php`, `profesor.eliminar.php`).
     - ABM de Usuarios (`usuario.crear.php`, `usuario.modificar.php`, `usuario.eliminar.php`).
     - ABM de Roles (`rol.crear.php`, `rol.modificar.php`, `rol.eliminar.php`).
     - ABM de Permisos (`permiso.crear.php`, `permiso.modificar.php`, `permiso.eliminar.php`).
     - Carga y actualización de programas (`programa.crear.php`, `programa.modificar.pdf.php`, `subir.programa.formulario.php`).
     - Modales de revisión, subida de firmado, aprobación final y avance en `revisar.programa.pdf.php`.
     - Formulario de envío a revisión en `vista/inicio.php`.
  4. En los respectivos scripts de procesamiento en backend, invocar `ControlAcceso::requiereCsrf()`, rechazando con HTTP 403 Forbidden cualquier solicitud que carezca de un token válido y coincidente con la sesión activa.
- **Consecuencias:** Se bloquean de raíz los ataques de CSRF en toda la plataforma sin afectar la experiencia de usuario ni la suite de pruebas automatizadas.

---

## ADR-013: Mitigación de Compatibilidad y Saneamiento en Dependencias de Terceros (`lib/`)
- **Fecha:** 2026-11-02
- **Estado:** Implementada
- **Hallazgo:** `SEC-013`
- **Contexto:** Componentes heredados en el directorio `lib/` (tales como `PHPMailer 5.x` y `TCPDF`) presentaban errores de sintaxis obsoleta incompatible con PHP 8.2 (acceso a cadenas y arreglos mediante llaves `{}`) y se habían generado carpetas de respaldo huérfanas en el directorio de librerías.
- **Decisión:**
  1. Corregir puntualmente la sintaxis deprecada en `lib/PHPMailer/extras/htmlfilter.php:351` reemplazando `$attary{$attname}` por la notación de corchetes estándar `$attary[$attname]`.
  2. Verificar la eliminación de carpetas de backup redundantes en `lib/` para asegurar un repositorio limpio y consistente.
  3. Planificar la migración estructural a versiones modernas gestionadas mediante Composer para la Fase 4.
- **Consecuencias:** Se restablece la compatibilidad total con PHP 8.2+ eliminando errores fatales de parseo sin requerir una reestructuración de dependencias prematura.

---

## ADR-014: Manejo Seguro de Sesiones y Atributos de Cookies
- **Fecha:** 2026-11-03
- **Estado:** Implementada
- **Hallazgo:** `SEC-007`
- **Contexto:** Por defecto, PHP emite la cookie de sesión (`PHPSESSID`) sin banderas de seguridad (`HttpOnly`, `SameSite=Lax`, `Secure`), dejándola vulnerable a exfiltración mediante Cross-Site Scripting (XSS) y manipulación en peticiones entre sitios. Asimismo, en el cierre de sesión (`app/salir.php`) no se purgaban las variables en memoria ni se eliminaba la cookie en el cliente, permitiendo el reuso potencial de identificadores de sesión.
- **Decisión:**
  1. Centralizar la inicialización de sesiones en `ControlAcceso::iniciarSesionSegura()` utilizando `session_set_cookie_params()` y directivas de configuración previas a `session_start()`:
     - `httponly = true` para denegar acceso a la cookie desde scripts del DOM.
     - `samesite = 'Lax'` para mitigar CSRF cruzado.
     - `secure = true` condicionado a conexiones cifradas HTTPS (`!empty($_SERVER['HTTPS'])` o puerto 443).
     - `session.use_only_cookies = 1` para impedir la propagación de identificadores por query string.
  2. Garantizar la regeneración de identificadores de sesión (`session_regenerate_id(true)`) tras autenticación válida.
  3. En `app/salir.php`, implementar la destrucción integral en tres pasos: purga de `$_SESSION = []`, borrado explícito de la cookie en el navegador con fecha en el pasado (`time() - 42000`) respetando los atributos del dominio/ruta, e invocación a `session_destroy()`.
- **Consecuencias:** Se blinda el ciclo de vida de la sesión contra ataques de secuestro y fijación de sesión tanto en desarrollo como en despliegues institucionales.

---

## ADR-015: Supresión de Errores en Producción y Cabeceras de Seguridad HTTP
- **Fecha:** 2026-11-03
- **Estado:** Implementada
- **Hallazgos:** `SEC-010`, `SEC-016`
- **Contexto:** Múltiples puntos de entrada y endpoints del sistema forzaban `ini_set('display_errors', 1)` de forma estática, provocando la exposición de trazas de pila, rutas locales del servidor y detalles internos ante excepciones. Adicionalmente, el servidor web y los scripts carecían de cabeceras de respuesta que restrinjan la interpretación de tipos MIME erróneos, clickjacking o scripts externos no confiables.
- **Decisión:**
  1. Centralizar el manejo de errores en `ControlAcceso::configurarEntorno()` vinculándolo estrictamente a la variable de entorno `APP_DEBUG` de `.env`: en entornos no locales o con debug desactivado, se fuerza `display_errors = 0` y `display_startup_errors = 0`, asegurando `log_errors = 1` para auditoría interna en logs.
  2. Retirar directivas `ini_set('display_errors', 1)` hardcodeadas en `app/index.php`, `gui/navbar.php` y endpoints AJAX en `lib/consultaAjax/informeGerencial/`.
  3. Inyectar mediante `mod_headers` en el `.htaccess` perimetral raíz (y como defensa en profundidad en PHP) las siguientes cabeceras:
     - `X-Content-Type-Options: nosniff` (previene ataques MIME sniffing).
     - `X-Frame-Options: SAMEORIGIN` (mitiga clickjacking).
     - `Referrer-Policy: strict-origin-when-cross-origin` (controla fuga de rutas internas en cabecera Referer).
     - `Content-Security-Policy`: política funcional equilibrada que autoriza recursos locales propios, scripts y autenticación de Google APIs (`apis.google.com`, `accounts.google.com`), tipografías de Google Fonts y hojas de estilo locales sin comprometer los plugins Bootstrap/jQuery.
  4. Mantener activa la directiva perimetral `Options -Indexes` en `.htaccess` para bloquear la navegación de directorios y el reconocimiento de archivos.
- **Consecuencias:** Se elimina la fuga de información técnica a usuarios finales y atacantes, blindando al navegador contra ataques modernos del lado del cliente.

---

## ADR-016: Mitigación de Exposición de Base de Datos y Endurecimiento de Entorno
- **Fecha:** 2026-11-03
- **Estado:** Implementada
- **Hallazgo:** `SEC-012`
- **Contexto:** En la instalación base de MariaDB/MySQL bajo XAMPP, el servicio escucha por omisión en todas las interfaces de red (`0.0.0.0:3306`), lo que expone el puerto de la base de datos ante eventuales equipos en la red local. Además, se requiere estandarizar lineamientos de hardening para Apache, PHP y MariaDB previo a producción.
- **Decisión:**
  1. Configurar en `c:\xampp\mysql\bin\my.ini` la directiva `bind-address = 127.0.0.1` dentro del bloque `[mysqld]`, asegurando que el motor de base de datos responda exclusivamente a conexiones originadas desde la interfaz loopback local.
  2. Generar el documento técnico [`docs/seguridad/hardening-entorno-xampp.md`](./docs/seguridad/hardening-entorno-xampp.md) que establece lineamientos para menores privilegios de usuarios MySQL, remoción de cuentas anónimas, configuración de `ServerTokens Prod`, `ServerSignature Off`, `expose_php = Off` y checklist de verificación.
- **Consecuencias:** Se neutraliza la exposición del puerto MySQL a vectores de ataque provenientes de la red, facilitando un traspaso seguro hacia la infraestructura de producción.

---

## ADR-017: Gestión Estándar de Dependencias y Autoloading con Composer
- **Fecha:** 2026-11-03
- **Estado:** Implementada
- **Contexto:** El proyecto VASPA carecía de un manifiesto formal de dependencias (`composer.json`), basando la carga de librerías en inclusión manual o rutas fijas en `lib/`. Además, el código legado presentaba inconsistencias sintácticas con PHP 8.2 (notación de llaves para acceso a elementos de arreglos/cadenas en `lib/tcpdf/`) y no existían scripts estandarizados para linting o validación del empaquetado.
- **Decisión:**
  1. Crear `composer.json` especificando versión base PHP `^8.2`, extensiones obligatorias del sistema (`ext-mysqli`, `ext-pdo`, `ext-pdo_mysql`, `ext-mbstring`, `ext-fileinfo`, `ext-json`), y librerías base (`phpmailer/phpmailer: ^6.9`, `vlucas/phpdotenv: ^5.6`).
  2. Configurar autoloading estándar mediante `classmap` abarcando las clases del dominio en `modelo/`, `modeloSistema/`, `controlSistema/`, `lib/` y utilitarios.
  3. Crear el script multiplataforma optimizado `scripts/lint.php` invocable vía `composer lint` que valida sintácticamente con `php -l` el 100% de los archivos PHP del proyecto excluyendo dependencias externas de prueba.
  4. Corregir los 7 accesos con llaves deprecados en `lib/tcpdf/tcpdf_barcodes_1d.php` y `lib/tcpdf/tcpdf_parser.php`, garantizando conformidad absoluta con PHP 8.2+.
- **Consecuencias:** El proyecto adquiere un estándar moderno de empaquetado PHP, interoperabilidad con gestores de paquetes y verificación estricta con `composer validate --strict`.

---

## ADR-018: Contenedorización Multi-Entorno con Docker y Docker Compose
- **Fecha:** 2026-11-03
- **Estado:** Implementada
- **Contexto:** Se requería garantizar la reproducibilidad exacta del entorno de ejecución de VASPA tanto para desarrollo, pruebas automatizadas y despliegue en producción sin depender exclusivamente de una instalación local de XAMPP.
- **Decisión:**
  1. Crear `Dockerfile` basado en la imagen oficial `php:8.2-apache`, configurando las extensiones requeridas (`mysqli`, `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `zip`, `opcache`), habilitando los módulos Apache `mod_rewrite`, `mod_headers` y `ssl`, y aplicando directivas estrictas de seguridad (`ServerTokens Prod`, `ServerSignature Off`, `expose_php = Off`, `display_errors = Off`).
  2. Crear `.dockerignore` excluyendo rigurosamente secretos (`.env`), directorios de agentes, dependencias de Node.js, artefactos de pruebas y volcados de bases de datos.
  3. Crear `docker-compose.yml` para orquestar los servicios `app` (VASPA web en puerto configurable `8080`) y `db` (MariaDB 10.6 en puerto host `3307` con inicialización automática desde `scriptBD/` montado en `/docker-entrypoint-initdb.d/`), persistencia de volúmenes para base de datos y archivos PDF, y healthchecks para sincronización de arranque.
- **Consecuencias:** Cualquier desarrollador o servidor puede desplegar la infraestructura completa de VASPA mediante un único comando `docker compose up -d`, asegurando paridad de entornos y aislamiento de servicios.

---

## ADR-019: Pipeline Automatizado de Integración Continua (CI/CD) con GitHub Actions y Suite E2E Renovada
- **Fecha:** 2026-11-03
- **Estado:** Implementada
- **Contexto:** Para evitar regresiones de código y vulnerabilidades en futuras iteraciones, es necesario contar con un pipeline automatizado de integración continua que valide sintaxis, reglas de seguridad y ejecución de pruebas de extremo a extremo (E2E) en cada cambio.
- **Decisión:**
  1. Crear el flujo de trabajo en `.github/workflows/ci.yml` con ejecución en eventos `push` y `pull_request` sobre ramas principales (`main`, `master`, `develop`).
  2. Implementar 4 jobs modulares:
     - `php-lint-and-composer`: Validación estricta de `composer.json` y linting sintáctico total mediante `php scripts/lint.php`.
     - `security-audit`: Verificación de ausencia de archivos de secretos `.env`, presencia de `.env.example` y existencia de todos los archivos perimetrales `.htaccess`.
     - `docker-build`: Construcción y verificación de la imagen de producción/desarrollo desde `Dockerfile`.
     - `e2e-playwright-tests`: Despliegue de servicio MariaDB efímero, inicialización de esquemas `scriptBD/`, arranque de servidor web PHP y ejecución completa de la suite Playwright.
  3. Renovar y expandir la suite de pruebas E2E incorporando el módulo `tests/e2e/specs/10-seguridad-endurecimiento.spec.js` con 5 nuevos casos de prueba automatizados (`CP-E2E-36` a `CP-E2E-40`), alcanzando 40 pruebas automatizadas que cubren exhaustivamente cabeceras de seguridad, flags de sesión, bloqueo de scripts CLI, denegación perimetral a bases de datos y protección Anti-CSRF.
- **Consecuencias:** Se establece una garantía continua de calidad, estabilidad y seguridad respaldada por pruebas automatizadas y trazabilidad completa.

