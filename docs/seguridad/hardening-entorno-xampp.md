# Guía de Endurecimiento de Entorno (Hardening) — VASPA
## Mitigación de Vulnerabilidades de Infraestructura y Servicios (SEC-012)

Esta guía documenta los lineamientos, procedimientos técnicos y configuraciones requeridas para proteger el entorno de ejecución del sistema VASPA (Apache, PHP, MariaDB/MySQL) tanto en desarrollo local controlado como en servidores de prueba e implementación institucional.

---

## 1. Endurecimiento de Base de Datos (MariaDB / MySQL)

### 1.1 Restricción de Interfaces de Red (`bind-address`)
Por defecto, muchas instalaciones locales de MySQL/MariaDB escuchan en todas las interfaces de red (`0.0.0.0:3306`), lo que expone el motor de base de datos a accesos no autorizados provenientes de la red local (LAN) o externa.

**Mitigación aplicada:**
En el archivo de configuración del servicio MySQL (`my.ini` en Windows / `my.cnf` en Linux), dentro del bloque `[mysqld]`, se define explícitamente:

```ini
[mysqld]
# Restricción de escucha exclusivamente a la interfaz loopback local
bind-address = 127.0.0.1
port = 3306
```

Esto impide que cualquier host remoto establezca conexiones directas al puerto 3306.

### 1.2 Principio de Menor Privilegio para Usuarios de Base de Datos
- **Eliminar cuentas anónimas** y usuarios con contraseñas vacías.
- **No utilizar la cuenta `root`** para la conexión diaria de la aplicación.
- Crear un usuario específico para la aplicación VASPA con permisos estrictamente limitados a las bases de datos requeridas (`bdgef_vaspa` y `bdusuarios`):
  ```sql
  CREATE USER 'vaspa_app'@'127.0.0.1' IDENTIFIED BY 'STRONG_SECRET_PASSWORD_HERE';
  GRANT SELECT, INSERT, UPDATE, DELETE ON bdgef_vaspa.* TO 'vaspa_app'@'127.0.0.1';
  GRANT SELECT, INSERT, UPDATE, DELETE ON bdusuarios.* TO 'vaspa_app'@'127.0.0.1';
  FLUSH PRIVILEGES;
  ```

### 1.3 Bloqueo de Importación/Exportación de Archivos Arbitrarios
Configurar la directiva `secure-file-priv` para impedir lecturas y escrituras arbitrarias del sistema de archivos mediante sentencias SQL (`LOAD DATA INFILE`, `SELECT ... INTO OUTFILE`):
```ini
[mysqld]
secure-file-priv = "C:/xampp/tmp"
```

---

## 2. Endurecimiento del Servidor Web (Apache)

### 2.1 Ocultamiento de Metadatos y Versiones del Servidor
Por defecto, Apache y PHP exponen sus versiones exactas en las cabeceras HTTP de respuesta (`Server` y `X-Powered-By`), facilitando el reconocimiento a atacantes.

**Configuración recomendada en `httpd.conf`:**
```apache
# Ocultar detalles del servidor en cabeceras HTTP
ServerTokens Prod
ServerSignature Off
```

**Configuración en `php.ini`:**
```ini
# No exponer la versión de PHP en cabeceras HTTP
expose_php = Off
```

### 2.2 Desactivación del Listado de Directorios y Opciones Inseguras
En el archivo `.htaccess` raíz y en los directorios de subida:
```apache
# Desactivar navegación de directorios y ejecución de CGIs
Options -Indexes -ExecCGI
```

### 2.3 Cabeceras HTTP de Seguridad
Configurar cabeceras de defensa en profundidad mediante `mod_headers` en `.htaccess`:
```apache
<IfModule mod_headers.c>
    # Prevención de ataques de confusión de tipo MIME
    Header set X-Content-Type-Options "nosniff"

    # Prevención de ataques de Clickjacking
    Header set X-Frame-Options "SAMEORIGIN"

    # Política de información de referencia
    Header set Referrer-Policy "strict-origin-when-cross-origin"

    # Content Security Policy equilibrada para la plataforma
    Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://apis.google.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: https:; connect-src 'self' https://accounts.google.com https://apis.google.com; frame-src 'self' https://accounts.google.com;"
</IfModule>
```

---

## 3. Endurecimiento del Runtime PHP

### 3.1 Gestión de Errores según Entorno (SEC-010)
- En **Producción** (`APP_ENV=production`, `APP_DEBUG=false`):
  ```ini
  display_errors = Off
  display_startup_errors = Off
  log_errors = On
  error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
  ```
- En **Desarrollo** (`APP_ENV=local`, `APP_DEBUG=true`):
  Los errores pueden mostrarse en pantalla únicamente para depuración local pero nunca en entornos expuestos.

### 3.2 Manejo Seguro de Sesiones y Cookies (SEC-007)
Asegurar que todas las cookies de sesión se emitan con:
- `HttpOnly`: Impide el acceso a la cookie de sesión desde código JavaScript en el navegador (mitiga robo de sesión por XSS).
- `SameSite=Lax`: Mitiga el envío de la cookie de sesión en peticiones cruzadas originadas en sitios externos (defensa en profundidad contra CSRF).
- `Secure`: Forzar que la cookie se envíe exclusivamente a través de canales cifrados HTTPS.
- `session.use_only_cookies = 1`: Impide la propagación del ID de sesión por parámetros URL (`trans_sid`).
- Regeneración obligatoria de ID de sesión (`session_regenerate_id(true)`) tras autenticación.
- Destrucción y borrado explícito de la cookie caducada en el cierre de sesión (`app/salir.php`).

---

## 4. Checklist de Hardening para Despliegue

| Control | Descripción | Estado |
| :--- | :--- | :--- |
| **MySQL bind-address** | Configurado `bind-address = 127.0.0.1` en `my.ini` | Aplicado |
| **MySQL contraseñas** | Contraseñas no vacías para todas las cuentas activas | Verificado |
| **Apache Options** | `Options -Indexes` activo en `.htaccess` | Aplicado |
| **Apache Cabeceras** | `X-Content-Type-Options`, `X-Frame-Options`, `CSP` | Aplicado |
| **PHP display_errors** | Desactivado en entornos de producción / controlado por `APP_DEBUG` | Aplicado |
| **PHP Sesiones** | Flags `HttpOnly`, `SameSite=Lax`, `Secure` si HTTPS | Aplicado |
| **Protección .env y .sql** | Denegación HTTP 403 en `.htaccess` y `.gitignore` | Aplicado |
