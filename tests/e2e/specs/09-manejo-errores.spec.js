// @ts-check
const { test, expect } = require('@playwright/test');
const { USUARIOS_PRUEBA, iniciarSesionComo } = require('../helpers/auth.helper');
const { crearMonitor } = require('../helpers/logger.helper');

test.describe('Módulo 09: Manejo Seguro de Errores y Rutas Inexistentes', () => {

  test.afterEach(async ({ page }) => {
    try {
      await page.goto('app/salir.php');
      await page.waitForLoadState('networkidle');
    } catch (e) {
      // Ignorar
    }
  });

  test('CP-E2E-34: Parámetro id alfanumérico o inexistente en revisión de programa PDF', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-34');
    const u = USUARIOS_PRUEBA.vinculacion;

    // 1. Iniciar sesión como Vinculación Académica
    await iniciarSesionComo(page, u.email, u.nombre);

    // 2. Intentar ingresar un parámetro id alfanumérico corrupto / inyección
    await page.goto('vista/revisar.programa.pdf.php?id=invalido_99999');
    await page.waitForLoadState('networkidle');

    // 3. El controlador defensivo debe atraparlo y redirigir limpiamente a revisar.programas.php
    await expect(page).toHaveURL(/revisar\.programas\.php/);

    // 4. Verificar que no se exponga ningún Fatal error, Warning o stack trace crudo de PHP en pantalla
    const cuerpoTexto = await page.locator('body').innerText();
    expect(cuerpoTexto).not.toContain('Fatal error');
    expect(cuerpoTexto).not.toContain('Stack trace');
    expect(cuerpoTexto).not.toContain('Uncaught mysqli_sql_exception');

    await monitor.tomarCaptura('parametro_invalido_redireccion_limpia');
  });

  test('CP-E2E-35: Respuesta segura de Apache ante archivo físico inexistente (HTTP 404)', async ({ page, request }) => {
    const monitor = crearMonitor(page, 'CP-E2E-35');

    // 1. Petición directa a ruta inexistente
    const resp = await page.goto('vista/modulo_inexistente_e2e_prueba_404.php');

    // 2. Apache debe responder código HTTP 404 Not Found
    expect(resp?.status()).toBe(404);

    // 3. Verificar que la respuesta estándar de Apache no exponga stack traces PHP ni rutas internas
    const contenido = await page.content();
    expect(contenido).not.toContain('Fatal error:');
    expect(contenido).not.toContain('Stack trace:');

    await monitor.tomarCaptura('error_404_apache_limpio');
  });

});
