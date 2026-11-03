// @ts-check
const { test, expect } = require('@playwright/test');
const { USUARIOS_PRUEBA, iniciarSesionComo } = require('../helpers/auth.helper');
const { crearMonitor } = require('../helpers/logger.helper');

test.describe('Módulo 04: Navegación Integral por Vistas y Menús', () => {

  test.afterEach(async ({ page }) => {
    try {
      await page.goto('app/salir.php');
      await page.waitForLoadState('networkidle');
    } catch (e) {
      // Ignorar
    }
  });

  test('CP-E2E-14: Navegación integral para el rol Administrador', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-14');
    const u = USUARIOS_PRUEBA.admin;

    await iniciarSesionComo(page, u.email, u.nombre);
    await expect(page).toHaveURL(/app\/usuarios\.php/);

    const rutasAdmin = [
      { url: 'app/usuarios.php', palabraClave: 'Usuarios' },
      { url: 'app/roles.php', palabraClave: 'Roles' },
      { url: 'app/permisos.php', palabraClave: 'Permisos' },
      { url: 'vista/profesores.php', palabraClave: 'Profesor' },
    ];

    for (const item of rutasAdmin) {
      const resp = await page.goto(item.url);
      await page.waitForLoadState('networkidle');

      // Validar código de respuesta HTTP 200
      expect(resp?.status()).toBe(200);

      // Validar presencia de texto representativo en la página
      await expect(page.locator('body')).toContainText(item.palabraClave);

      // Si hay una tabla DataTable, verificar que esté presente en el DOM
      const tabla = page.locator('table.dataTable, table.table');
      if (await tabla.count() > 0) {
        await expect(tabla.first()).toBeVisible();
      }

      await monitor.tomarCaptura(`admin_nav_${item.palabraClave.toLowerCase()}`);
    }
  });

  test('CP-E2E-15: Navegación integral para el rol Vinculación Académica', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-15');
    const u = USUARIOS_PRUEBA.vinculacion;

    await iniciarSesionComo(page, u.email, u.nombre);
    await expect(page).toHaveURL(/vista\/inicio\.php/);

    const rutasVA = [
      { url: 'vista/panelVA.php', textoEsperado: 'Vigencia' },
      { url: 'vista/revisar.programas.php', textoEsperado: 'Programas' },
      { url: 'vista/monitoreo.circuito.php', textoEsperado: 'Circuito' },
      { url: 'vista/informeGerencial.programas.php', textoEsperado: 'Informe' },
      { url: 'vista/carreras.php', textoEsperado: 'Carreras' },
      { url: 'vista/planes.php', textoEsperado: 'Planes' },
      { url: 'vista/carreras.materias.php', textoEsperado: 'Materias' },
    ];

    for (const item of rutasVA) {
      const resp = await page.goto(item.url);
      await page.waitForLoadState('networkidle');

      // Validar código HTTP 200
      expect(resp?.status()).toBe(200);

      // Validar que el cuerpo contenga el contenido esperado
      await expect(page.locator('body')).toContainText(item.textoEsperado);

      await monitor.tomarCaptura(`va_nav_${item.textoEsperado.toLowerCase()}`);
    }
  });

});
