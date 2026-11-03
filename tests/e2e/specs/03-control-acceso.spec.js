// @ts-check
const { test, expect } = require('@playwright/test');
const { USUARIOS_PRUEBA, iniciarSesionComo } = require('../helpers/auth.helper');
const { crearMonitor } = require('../helpers/logger.helper');

test.describe('Módulo 03: Control de Acceso y Protección de Rutas (RBAC)', () => {

  test.afterEach(async ({ page }) => {
    try {
      await page.goto('app/salir.php');
      await page.waitForLoadState('networkidle');
    } catch (e) {
      // Ignorar
    }
  });

  test('CP-E2E-10: Acceso directo a pantalla Admin (app/usuarios.php) sin sesión activa', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-10');

    // Intentar acceder directamente a usuarios.php sin autenticación previa
    await page.goto('app/usuarios.php');
    await page.waitForLoadState('networkidle');

    // ControlAcceso::verificaLogin() debe expulsar a app/index.php
    await expect(page).toHaveURL(/app\/index\.php/);

    await monitor.tomarCaptura('acceso_denegado_usuarios_sin_sesion');
  });

  test('CP-E2E-11: Acceso directo a dashboard (vista/inicio.php) sin sesión activa', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-11');

    await page.goto('vista/inicio.php');
    await page.waitForLoadState('networkidle');

    // ControlAcceso::verificaLogin() debe expulsar a app/index.php
    await expect(page).toHaveURL(/app\/index\.php/);

    await monitor.tomarCaptura('acceso_denegado_inicio_sin_sesion');
  });

  test('CP-E2E-12: Acceso a tablero de monitoreo (vista/monitoreo.circuito.php) con rol Profesor sin privilegios', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-12');
    const u = USUARIOS_PRUEBA.profesor;

    // 1. Iniciar sesión como Profesor
    await iniciarSesionComo(page, u.email, u.nombre);
    await expect(page).toHaveURL(/vista\/inicio\.php/);

    // 2. Intentar ingresar manualmente a la URL de monitoreo reservada para VA
    await page.goto('vista/monitoreo.circuito.php');
    await page.waitForLoadState('networkidle');

    // El sistema debe restringir o expulsar fuera del tablero privado de monitoreo
    const urlActual = page.url();
    const esBloqueado = !urlActual.includes('monitoreo.circuito.php') || (await page.locator('text=No tiene permisos, text=Acceso Denegado, text=Error').count() > 0);
    expect(esBloqueado).toBeTruthy();

    await monitor.tomarCaptura('acceso_profesor_monitoreo_bloqueado');
  });

  test('CP-E2E-13: Acceso a gestión de roles (app/roles.php) con rol Profesor', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-13');
    const u = USUARIOS_PRUEBA.profesor;

    // 1. Iniciar sesión como Profesor
    await iniciarSesionComo(page, u.email, u.nombre);
    await expect(page).toHaveURL(/vista\/inicio\.php/);

    // 2. Intentar navegar a app/roles.php (requiere PERMISO_ROLES)
    await page.goto('app/roles.php');
    await page.waitForLoadState('networkidle');

    // ControlAcceso::requierePermiso() debe redirigir a app/index.php
    await expect(page).toHaveURL(/app\/index\.php/);

    await monitor.tomarCaptura('acceso_profesor_roles_expulsado');
  });

});
