// @ts-check
const { test, expect } = require('@playwright/test');
const { USUARIOS_PRUEBA, iniciarSesionComo } = require('../helpers/auth.helper');
const { crearMonitor } = require('../helpers/logger.helper');

test.describe('Módulo 02: Sesión y Cierre Seguro (Logout)', () => {

  test('CP-E2E-09: Cierre de sesión voluntario en app/salir.php y destrucción de sesión', async ({ page, context }) => {
    const monitor = crearMonitor(page, 'CP-E2E-09');
    const u = USUARIOS_PRUEBA.admin;

    // 1. Iniciar sesión previamente
    await iniciarSesionComo(page, u.email, u.nombre);
    await expect(page).toHaveURL(/app\/usuarios\.php/);

    // 2. Ejecutar logout haciendo clic en Salir o accediendo a app/salir.php
    const salirLink = page.locator('a[href*="salir.php"]');
    if (await salirLink.count() > 0) {
      await salirLink.first().click();
    } else {
      await page.goto('app/salir.php');
    }

    await page.waitForLoadState('networkidle');

    // 3. Verificar que cargó la pantalla de confirmación de salida
    await expect(page).toHaveURL(/app\/salir\.php/);
    await expect(page.locator('body')).toContainText('Ha salido del sistema');
    
    // Verificar presencia del botón o enlace para volver a ingresar
    const btnVolver = page.locator('a[href*="index.php"]');
    await expect(btnVolver).toBeVisible();

    await monitor.tomarCaptura('pantalla_salir_confirmada');

    // 4. Verificar que la sesión fue efectivamente destruida en el servidor:
    // Al intentar navegar directamente a una ruta protegida, debe expulsar al login
    await page.goto('app/usuarios.php');
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveURL(/app\/index\.php/);

    await page.goto('vista/inicio.php');
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveURL(/app\/index\.php/);

    await monitor.tomarCaptura('sesion_destruida_expulsion');
  });

});
