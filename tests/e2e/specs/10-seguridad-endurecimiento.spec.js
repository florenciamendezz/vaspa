// @ts-check
const { test, expect } = require('@playwright/test');
const { USUARIOS_PRUEBA, iniciarSesionComo } = require('../helpers/auth.helper');
const { crearMonitor } = require('../helpers/logger.helper');

test.describe('Módulo 10: Controles de Seguridad, Endurecimiento y Protección Perimetral', () => {

  test.afterEach(async ({ page }) => {
    try {
      await page.goto('app/salir.php');
      await page.waitForLoadState('networkidle');
    } catch (e) {
      // Ignorar excepciones al cerrar sesión
    }
  });

  test('CP-E2E-36: Cabeceras HTTP de seguridad perimetrales (X-Content-Type-Options, X-Frame-Options, CSP, Referrer-Policy)', async ({ page, request }) => {
    const monitor = crearMonitor(page, 'CP-E2E-36');

    // 1. Realizar solicitud HTTP al punto de entrada principal
    const response = await request.get('app/index.php');
    expect(response.status()).toBe(200);

    const headers = response.headers();

    // 2. Verificar cabecera X-Content-Type-Options: nosniff
    expect(headers['x-content-type-options']).toBe('nosniff');

    // 3. Verificar cabecera X-Frame-Options: SAMEORIGIN
    expect(headers['x-frame-options']).toBe('SAMEORIGIN');

    // 4. Verificar cabecera Referrer-Policy: strict-origin-when-cross-origin
    expect(headers['referrer-policy']).toBe('strict-origin-when-cross-origin');

    // 5. Verificar presencia de Content-Security-Policy
    expect(headers['content-security-policy']).toBeDefined();
    expect(headers['content-security-policy']).toContain("default-src 'self'");

    // 6. Navegar a la página y capturar evidencia visual
    await page.goto('app/index.php');
    await page.waitForLoadState('networkidle');
    await monitor.tomarCaptura('cabeceras_seguridad_verificadas');
  });

  test('CP-E2E-37: Atributos de seguridad en cookies de sesión (HttpOnly, SameSite=Lax)', async ({ page, context }) => {
    const monitor = crearMonitor(page, 'CP-E2E-37');
    const u = USUARIOS_PRUEBA.admin;

    // 1. Iniciar sesión autenticada
    await iniciarSesionComo(page, u.email, u.nombre);

    // 2. Obtener las cookies del contexto de navegación
    const cookies = await context.cookies();
    const sessionCookie = cookies.find(c => c.name === 'PHPSESSID');

    expect(sessionCookie).toBeDefined();

    // 3. Validar flag HttpOnly activo (inmune a robo de sesión por XSS en DOM)
    expect(sessionCookie?.httpOnly).toBe(true);

    // 4. Validar flag SameSite=Lax activo (mitigación de peticiones entre sitios no confiables)
    expect(sessionCookie?.sameSite).toBe('Lax');

    await monitor.tomarCaptura('cookie_sesion_segura_confirmada');
  });

  test('CP-E2E-38: Bloqueo de acceso perimetral a volcados SQL y directorio scriptBD (HTTP 403 Forbidden)', async ({ request }) => {
    // 1. Intentar acceder a scriptBD/01_bdusuarios.sql
    const respSql = await request.get('scriptBD/01_bdusuarios.sql');
    expect(respSql.status()).toBe(403);

    // 2. Intentar acceder a scriptBD/02_ESTRUCTURA - bdgef_vaspa.sql
    const respEstructura = await request.get('scriptBD/02_ESTRUCTURA%20-%20bdgef_vaspa.sql');
    expect(respEstructura.status()).toBe(403);

    // 3. Intentar acceder al directorio scriptBD/ directamente
    const respDir = await request.get('scriptBD/');
    expect(respDir.status()).toBe(403);
  });

  test('CP-E2E-39: Bloqueo de scripts de mantenimiento ante peticiones web (CLI obligatorio, HTTP 403)', async ({ request }) => {
    // 1. Intentar acceder a create_log_table.php vía HTTP
    const respLog = await request.get('create_log_table.php');
    expect(respLog.status()).toBe(403);
    const bodyLog = await respLog.text();
    expect(bodyLog).toContain('CLI');

    // 2. Intentar acceder a migrate_SA_to_VA.php vía HTTP
    const respMigrate = await request.get('migrate_SA_to_VA.php');
    expect(respMigrate.status()).toBe(403);

    // 3. Intentar acceder a verify_multicast_data.php vía HTTP
    const respVerify = await request.get('verify_multicast_data.php');
    expect(respVerify.status()).toBe(403);
  });

  test('CP-E2E-40: Protección Anti-CSRF ante peticiones POST forjadas sin token válido', async ({ page, request }) => {
    const monitor = crearMonitor(page, 'CP-E2E-40');
    const u = USUARIOS_PRUEBA.admin;

    // 1. Iniciar sesión como Administrador
    await iniciarSesionComo(page, u.email, u.nombre);

    // 2. Realizar petición POST forjada a un endpoint de cambio de estado omitiendo el token CSRF
    const respPost = await page.request.post('controlSistema/programa.enviar.revision.php', {
      form: {
        idPrograma: 99999,
        // Sin token CSRF
      }
    });

    // 3. El servidor debe rechazar la petición con 403 Forbidden o redirección defensiva
    expect([403, 302]).toContain(respPost.status());

    // 4. Si es 403, verificar que contenga mensaje de token inválido
    if (respPost.status() === 403) {
      const respTexto = await respPost.text();
      expect(respTexto).toMatch(/CSRF|Token|denegad/i);
    }

    await monitor.tomarCaptura('csrf_post_forjado_rechazado');
  });

});
