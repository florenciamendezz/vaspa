# Memoria de Trabajo y Estado de Avance — VASPA

> **Última actualización:** 2026-11-03  
> **Fase actual:** Finalización exitosa de **Fase 4 (Mantenimiento Continuo, DevOps, Docker, Composer y CI/CD)**. **PROYECTO 100% COMPLETADO**.  
> **Suite E2E de referencia:** 40/40 tests pasando en `tests/e2e/` (`npx playwright test`).

---

## 1. Resumen de Estado por Fases

| Fase | Descripción | Plazo Sugerido | Estado | Progreso |
| :--- | :--- | :--- | :--- | :--- |
| **Fase 0** | Auditoría de Seguridad + Suite QA E2E (35 tests) + Trazabilidad | Completado | **COMPLETADO** | 100% |
| **Fase 1** | Remediación Crítica / Inmediata (`SEC-001`, `SEC-002`, `SEC-009`, `SEC-014`, `SEC-015`) | 24 - 48 hs | **COMPLETADO** | 100% (5/5) |
| **Fase 2** | Remediación de Severidad Alta (`SEC-003`, `SEC-004`, `SEC-005`, `SEC-006`, `SEC-008`, `SEC-011`, `SEC-013`) | 1 - 2 semanas | **COMPLETADO** | 100% (7/7) |
| **Fase 3** | Endurecimiento de Entorno y Severidad Media (`SEC-007`, `SEC-010`, `SEC-012`, `SEC-016`) | 1 mes | **COMPLETADO** | 100% (4/4) |
| **Fase 4** | Mejora Continua (Composer, Docker, CI/CD y Suite E2E Renovada) | 2 - 3 meses | **COMPLETADO** | 100% (4/4) |

---

## 2. Checklist Detallado de Remediación (Hallazgos `SEC-001` a `SEC-016`)

### Prioridad 1 — Crítica (Fase 1)
- [x] **SEC-009 y SEC-015 (Secretos y credenciales en texto plano)**:
  - Archivos afectados: `lib/notificacionesMail/constantesMail.php`, `lib/funcionesUtiles/constantesMail.php`, `modelo/BDConexion.Class.php`, `modeloSistema/BDConexionSistema.Class.php`, `tools.yaml`.
  - Acción: Centralizar carga desde variables de entorno / archivo `.env` (no versionado), crear `.env.example` con valores enmascarados, retirar `tools.yaml` del webroot y actualizar `.gitignore`. *(Implementado y verificado)*.
- [x] **SEC-014 (Volcados `.sql` públicos en el webroot)**:
  - Archivos afectados: `scriptBD/*.sql` y archivos `backup_bdgef_vaspa_*.sql` en la raíz.
  - Acción: Bloquear acceso HTTP a `scriptBD/` y archivos `.sql` (vía `.htaccess` con `Require all denied` y `.htaccess` perimetral en raíz con `Options -Indexes`) y limpiar backups sueltos en raíz. *(Implementado y verificado con HTTP 403)*.
- [x] **SEC-002 (Bypass de autenticación y backdoor de pruebas)**:
  - Archivos afectados: `login_pruebas.html`, `app/index.php` (bloque `collapseLoginPruebas`), `lib/ControlAcceso.Class.php`, `lib/login.js`.
  - Acción: Restringir el login de pruebas exclusivamente a entorno de desarrollo/test controlado por variable de entorno (`APP_ENV=local` / flag seguro `ControlAcceso::esEntornoPruebas()`), eliminar `login_pruebas.html`, sanear sesión con `unset($_SESSION['usuario'])`, regeneración de ID (`session_regenerate_id(true)`) y token CSRF. *(Implementado y verificado con 100% no-regresión en E2E)*.
- [x] **SEC-001 (Inyección SQL por concatenación directa)**:
  - Archivos críticos iniciales: `lib/ControlAcceso.Class.php`, `modelo/BDObjetoGenerico.Class.php`, `app/usuario.*.procesar.php`, `controlSistema/ManejadorCarrera.php`, `controlSistema/ManejadorProfesor.php`, endpoints AJAX en `lib/consultaAjax/`.
  - Acción: Parametrizar consultas mediante sentencias preparadas (`prepare` / `bind_param`), casteo estricto de enteros (`intval`), validación por regex en identificadores de tabla y retiro de `die(errno)`. *(Implementado y verificado con prueba de penetración y suite E2E)*.

### Prioridad 2 — Alta (Fase 2)
- [x] **SEC-005 (Scripts DDL/migración y endpoints sin autenticación en webroot)**:
  - Archivos: `create_log_table.php`, `migrate_SA_to_VA.php`, `update_db_schema_escuela.php`, `get_valid_asignatura.php`, `verify_multicast_data.php`, `cron/avisoRetrasoCircuito.php`, `cron/.htaccess`, `generarReporteGerencial.php`, `vista/carrera.verAsignaturas.php`, endpoints AJAX en `lib/consultaAjax/`.
  - Acción: Restringir ejecución de scripts administrativos y tareas cron a modo CLI (`php_sapi_name() !== 'cli'` con HTTP 403), denegación perimetral en `cron/.htaccess`, protección de endpoints AJAX con `verificaLogin()` y roles en sesión, preservando los endpoints públicos comunitarios (`visualizar.programa.cargar.carreras.php` y `visualizarPrograma/tablaProgramasAsignaturas.php`). *(Implementado y verificado con CP-E2E-16)*.
- [x] **SEC-006 (Bypass de sesión en descarga/generación de PDF)**:
  - Archivos: `vista/programa.descargarPDF.php`, `vista/programa.generarPDF.php`.
  - Acción: Control de acceso RBAC estricto (`verificaLogin()`), casteo `intval`, sentencias preparadas y validación canónica con `realpath()` para evitar Directory Traversal (`strpos($rutaReal, $baseDir) === 0`). *(Implementado y verificado)*.
- [x] **SEC-008 (Subida insegura de archivos PDF y Path Traversal)**:
  - Archivos: `archivos/.htaccess`, `programas/.htaccess`, `vista/subir.programa.procesar.php`, `controlSistema/programa.crear.procesar.pdf.php`, `controlSistema/programa.actualizar.pdf.php`, `controlSistema/programa.modificar.procesar.pdf.php`.
  - Acción: Creación de reglas perimetrales `.htaccess` bloqueando ejecución de scripts (`Options -ExecCGI -Indexes`, `FilesMatch`, `php_flag engine off`, `X-Content-Type-Options: nosniff`), validación estricta de Magic Bytes / Tipo MIME Real con `finfo(FILEINFO_MIME_TYPE) === 'application/pdf'`, límite de 10 MB y saneamiento con `basename()`. *(Implementado y verificado con CP-E2E-27 y CP-E2E-29)*.
- [x] **SEC-011 (IDOR en revisión de programas analíticos)**:
  - Archivos: `controlSistema/programa.revisar.actualizar.estado.pdf.php`, `controlSistema/programa.actualizar.pdf.php`, `controlSistema/programa.enviar.revision.php`, `vista/revisar.programas.php:33-38`.
  - Acción: Corrección de asignación de departamentos invertidos CNE (1) vs CS (2) y validación contextual de jurisdicción académica (Departamento, Escuela y Cátedra) denegando con HTTP 403 transgresiones de ámbito. *(Implementado y verificado con CP-E2E-28, CP-E2E-29 y CP-E2E-30)*.
- [x] **SEC-004 (Cross-Site Scripting — XSS en navbar y vistas)**:
  - Archivos: `lib/ControlAcceso.Class.php` (helper global `e()`), `gui/navbar.php`, `app/usuario.*.procesar.php`, `app/rol.*.procesar.php`, `app/permiso.*.procesar.php`, `app/usuarios.php`, `app/roles.php`, `app/permisos.php`, `vista/carreras.php`, `vista/carrera.verPlanes.php`, `vista/profesores.php`.
  - Acción: Sanitización contextual y codificación HTML de entidades con `e()` y codificación URL con `urlencode()` en salidas dinámicas del DOM y redirecciones. *(Implementado y verificado)*.
- [x] **SEC-003 (Ausencia de protección Anti-CSRF)**:
  - Archivos: `lib/ControlAcceso.Class.php`, `gui/navbar.php`, formularios POST en `app/` y `vista/`, y procesadores en `app/`, `vista/` y `controlSistema/`.
  - Acción: Generación de token sincronizado con `random_bytes(32)`, inyección en meta tag y cabecera global jQuery (`X-CSRF-Token`), campo `ControlAcceso::campoCsrf()` en todos los formularios POST, y validación estricta con `ControlAcceso::requiereCsrf()`. *(Implementado y verificado en 35/35 tests E2E)*.
- [x] **SEC-013 (Dependencias obsoletas en `lib/`)**:
  - Archivos: `lib/PHPMailer/extras/htmlfilter.php:351`, limpieza de backups huérfanos.
  - Acción: Corrección de sintaxis de llaves deprecada a corchetes (`$attary[$attname]`) para compatibilidad completa con PHP 8.2+. *(Implementado y verificado con php -l)*.

### Prioridad 3 — Media (Fase 3)
- [x] **SEC-007 (Manejo de sesiones y flags de cookies `HttpOnly`, `SameSite`, regeneración de ID)**:
  - Archivos: `lib/ControlAcceso.Class.php`, `app/salir.php`, `vista/lista.asignaturas.php`, `vista/lista.asignaturas.correlativas.php`.
  - Acción: Inicialización centralizada con `ControlAcceso::iniciarSesionSegura()` forzando `session_set_cookie_params()` con `httponly=true`, `samesite=Lax`, `secure` condicional a HTTPS y `session.use_only_cookies=1`. Regeneración obligatoria de identificador (`session_regenerate_id(true)`) tras login exitoso. Cierre y purga limpia de sesión y cookie con expiración explícita en `app/salir.php`. *(Implementado y verificado con CP-E2E-09)*.
- [x] **SEC-010 y SEC-016 (Modo debug `display_errors`, cabeceras HTTP de seguridad y `Options -Indexes`)**:
  - Archivos: `app/index.php`, `gui/navbar.php`, `lib/ControlAcceso.Class.php`, `lib/consultaAjax/informeGerencial/`, `.htaccess` raíz.
  - Acción: Supresión centralizada de errores en pantalla (`display_errors = 0`, `log_errors = 1`) en `ControlAcceso::configurarEntorno()` supeditada a `Env::get('APP_DEBUG')`. Remoción de `ini_set('display_errors', 1)` hardcodeado en `app/index.php`, `gui/navbar.php` y endpoints de informes gerenciales. Inyección de cabeceras de seguridad perimetrales (`X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Content-Security-Policy`) y directiva `Options -Indexes` bloqueando navegación de directorios (HTTP 403). *(Implementado y verificado)*.
- [x] **SEC-012 (Exposición de puerto MySQL `0.0.0.0:3306` y actualización de runtimes XAMPP)**:
  - Archivos: `c:\xampp\mysql\bin\my.ini`, `docs/seguridad/hardening-entorno-xampp.md`.
  - Acción: Configuración de `bind-address = 127.0.0.1` en `[mysqld]` de `my.ini` forzando escucha local exclusiva. Creación de guía integral de endurecimiento para MariaDB/MySQL, Apache y PHP en servidores de desarrollo y producción. *(Implementado y verificado)*.

### Prioridad 4 — Mantenimiento Continuo, DevOps y CI/CD (Fase 4)
- [x] **Composer y Autoloading Estándar**:
  - Archivos: `composer.json`, `scripts/lint.php`, `lib/tcpdf/tcpdf_barcodes_1d.php`, `lib/tcpdf/tcpdf_parser.php`.
  - Acción: Creación de manifiesto estándar `composer.json` para PHP 8.2+ con dependencias y autoload tipo `classmap` (`modelo/`, `modeloSistema/`, `controlSistema/`, `lib/`), scripts de proyecto (`composer lint`, `composer test`, `check-composer`), creación del script `scripts/lint.php` (validación de 301 archivos PHP al 100%) y resolución de 7 accesos con llaves obsoletos en `lib/tcpdf/`. *(Implementado y verificado con `composer validate --strict`)*.
- [x] **Contenedorización con Docker y Docker Compose**:
  - Archivos: `Dockerfile`, `docker-compose.yml`, `.dockerignore`, `.gitignore`.
  - Acción: Creación de imagen Docker basada en `php:8.2-apache` con extensiones de datos y seguridad (`mysqli`, `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `zip`, `opcache`), módulos Apache (`mod_rewrite`, `mod_headers`, `ssl`) y directivas de endurecimiento. Orquestación con `docker-compose.yml` para desacoplar app web (puerto 8080) y base de datos MariaDB 10.6 (puerto 3307 con healthcheck y auto-inicialización de `scriptBD/`). `.dockerignore` protegiendo secretos y artefactos. *(Implementado y verificado con `docker compose config`)*.
- [x] **Pipeline de Integración Continua (CI/CD)**:
  - Archivos: `.github/workflows/ci.yml`.
  - Acción: Implementación de pipeline en GitHub Actions con 4 jobs automatizados: linting de PHP y validación de Composer, auditoría de seguridad y archivos `.htaccess`, construcción y verificación de `Dockerfile`, y ejecución automatizada de la suite Playwright sobre MariaDB efímero. *(Implementado y verificado)*.
- [x] **Renovación y Expansión de la Suite QA E2E (40 Tests Automatizados)**:
  - Archivos: `tests/e2e/specs/10-seguridad-endurecimiento.spec.js`.
  - Acción: Incorporación de 5 nuevos casos de prueba automatizados (`CP-E2E-36` a `CP-E2E-40`) certificando cabeceras HTTP de seguridad, atributos `HttpOnly; SameSite=Lax` en cookies de sesión, bloqueo de directorios y esquemas SQL en `scriptBD/` (HTTP 403), restricción de scripts administrativos a CLI (HTTP 403), y protección Anti-CSRF ante peticiones POST forjadas. *(Implementado y verificado con 40/40 tests aprobados al 100%)*.


---

## 3. Notas Operativas Importantes
- **Ejecución de pruebas E2E**: Para validar que ninguna corrección rompa los flujos funcionales, ejecutar desde `c:\xampp\htdocs\vaspa\tests\e2e`:
  ```powershell
  npx playwright test
  ```
- **Regla de Oro**: Antes de dar por cerrado cualquier ítem de la lista superior, actualizar [`CHANGELOG.md`](./CHANGELOG.md), [`DECISIONES.md`](./DECISIONES.md) (si hubo decisión de diseño) y marcar el ítem en este archivo [`MEMORIA_TRABAJO.md`](./MEMORIA_TRABAJO.md).
