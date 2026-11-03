// @ts-check
const { test, expect } = require('@playwright/test');
const { USUARIOS_PRUEBA, iniciarSesionComo, cerrarSesion } = require('../helpers/auth.helper');
const { crearMonitor } = require('../helpers/logger.helper');

test.describe('Módulo 01: Autenticación y Control de Credenciales (VASPA)', () => {

  test.afterEach(async ({ page }) => {
    // Cerrar sesión después de cada prueba para aislar el estado
    try {
      await page.goto('app/salir.php');
      await page.waitForLoadState('networkidle');
    } catch (e) {
      // Ignorar si ya está cerrada
    }
  });

  test('CP-E2E-01: Login exitoso con rol Administrador', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-01');
    const u = USUARIOS_PRUEBA.admin;

    await iniciarSesionComo(page, u.email, u.nombre);

    // Debe redirigir a app/usuarios.php
    await expect(page).toHaveURL(/app\/usuarios\.php/);

    // Debe mostrar la barra de navegación con el menú Usuarios y el nombre del usuario
    await expect(page.locator('body')).toContainText('Usuarios');
    await expect(page.locator('nav.navbar')).toBeVisible();

    await monitor.tomarCaptura('login_admin_exitoso');
  });

  test('CP-E2E-02: Login exitoso con rol Vinculación Académica', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-02');
    const u = USUARIOS_PRUEBA.vinculacion;

    await iniciarSesionComo(page, u.email, u.nombre);

    // Debe redirigir a vista/inicio.php
    await expect(page).toHaveURL(/vista\/inicio\.php/);

    // Debe mostrar la bienvenida y los accesos de gestión académica
    await expect(page.locator('body')).toContainText('Vinculación');
    await monitor.tomarCaptura('login_va_exitoso');
  });

  test('CP-E2E-03: Login exitoso con rol Director de Escuela', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-03');
    const u = USUARIOS_PRUEBA.escuela;

    await iniciarSesionComo(page, u.email, u.nombre);

    await expect(page).toHaveURL(/vista\/inicio\.php/);
    await expect(page.locator('body')).toContainText('Escuela');
    await monitor.tomarCaptura('login_escuela_exitoso');
  });

  test('CP-E2E-04: Login exitoso con rol Director Depto CNE', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-04');
    const u = USUARIOS_PRUEBA.depto_cne;

    await iniciarSesionComo(page, u.email, u.nombre);

    await expect(page).toHaveURL(/vista\/inicio\.php/);
    await expect(page.locator('body')).toContainText('Departamento');
    await monitor.tomarCaptura('login_depto_cne_exitoso');
  });

  test('CP-E2E-05: Login exitoso con rol Director Depto CS', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-05');
    const u = USUARIOS_PRUEBA.depto_cs;

    await iniciarSesionComo(page, u.email, u.nombre);

    await expect(page).toHaveURL(/vista\/inicio\.php/);
    await expect(page.locator('body')).toContainText('Departamento');
    await monitor.tomarCaptura('login_depto_cs_exitoso');
  });

  test('CP-E2E-06: Login exitoso con rol Profesor', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-06');
    const u = USUARIOS_PRUEBA.profesor;

    await iniciarSesionComo(page, u.email, u.nombre);

    await expect(page).toHaveURL(/vista\/inicio\.php/);
    await expect(page.locator('body')).toContainText('Profesor');
    await monitor.tomarCaptura('login_profesor_exitoso');
  });

  test('CP-E2E-07: Login con credenciales no registradas', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-07');

    await page.goto('app/index.php');
    await page.waitForLoadState('networkidle');

    // Enviar datos inexistentes vía POST
    await page.evaluate(() => {
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'index.php';

      const emailInput = document.createElement('input');
      emailInput.type = 'hidden';
      emailInput.name = 'email';
      emailInput.value = 'noexiste_e2e_qa@uarg.unpa.edu.ar';
      form.appendChild(emailInput);

      const nombreInput = document.createElement('input');
      nombreInput.type = 'hidden';
      nombreInput.name = 'nombre';
      nombreInput.value = 'Usuario Fantasma QA';
      form.appendChild(nombreInput);

      document.body.appendChild(form);
      form.submit();
    });

    await page.waitForNavigation({ waitUntil: 'networkidle' });

    // Permanece en index.php
    await expect(page).toHaveURL(/app\/index\.php/);

    // Debe mostrar mensaje de error "Usuario No Autenticado."
    const alerta = page.locator('.alert-danger');
    await expect(alerta).toBeVisible();
    await expect(alerta).toContainText('Usuario No Autenticado');

    await monitor.tomarCaptura('login_invalido_rechazado');
  });

  test('CP-E2E-08: Intento de login con campos vacíos', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-08');

    await page.goto('app/index.php');
    await page.waitForLoadState('networkidle');

    // Enviar campos vacíos vía POST
    await page.evaluate(() => {
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'index.php';

      const emailInput = document.createElement('input');
      emailInput.type = 'hidden';
      emailInput.name = 'email';
      emailInput.value = '';
      form.appendChild(emailInput);

      const nombreInput = document.createElement('input');
      nombreInput.type = 'hidden';
      nombreInput.name = 'nombre';
      nombreInput.value = '';
      form.appendChild(nombreInput);

      document.body.appendChild(form);
      form.submit();
    });

    await page.waitForNavigation({ waitUntil: 'networkidle' });

    // No debe autenticarse ni redirigir a dashboard privado
    await expect(page).toHaveURL(/app\/index\.php/);
    await expect(page.locator('.alert-danger')).toBeVisible();

    await monitor.tomarCaptura('login_campos_vacios');
  });

});
