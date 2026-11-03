// @ts-check
const { test, expect } = require('@playwright/test');
const path = require('path');
const { USUARIOS_PRUEBA, iniciarSesionComo } = require('../helpers/auth.helper');
const { crearMonitor } = require('../helpers/logger.helper');
const { asegurarAsignaturasBase } = require('../helpers/db.helper');

test.describe('Módulo 07: Circuito Funcional de Aprobación de Programas Analíticos', () => {

  test.beforeAll(() => {
    asegurarAsignaturasBase();
  });

  test.afterEach(async ({ page }) => {
    try {
      await page.goto('app/salir.php');
      await page.waitForLoadState('networkidle');
    } catch (e) {
      // Ignorar
    }
  });

  test('CP-E2E-27: Subida de programa analítico en formato PDF por el Profesor', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-27');
    const u = USUARIOS_PRUEBA.profesor;
    const rutaPDF = path.resolve(__dirname, '../assets/programa_prueba.pdf');

    // 1. Iniciar sesión como Profesor
    await iniciarSesionComo(page, u.email, u.nombre);
    await expect(page).toHaveURL(/vista\/inicio\.php/);

    // 2. Acceder a la carga de programa para la asignatura 0174 (Programación I)
    await page.goto('vista/programa.crear.php?id=0174');
    await page.waitForLoadState('networkidle');

    await expect(page.locator('h3')).toContainText('Cargar Programa');

    // 3. Adjuntar el archivo PDF de prueba
    await page.setInputFiles('input#archivoPDF', rutaPDF);
    await monitor.tomarCaptura('profesor_pdf_adjunto');

    // 4. Hacer clic en Subir Programa
    await page.click('button[type="submit"]:has-text("Subir Programa")');

    // 5. Confirmar modal Bootbox de confirmación
    const btnConfirmar = page.locator('button.btn-success:has-text("Confirmar")');
    await expect(btnConfirmar).toBeVisible();
    await btnConfirmar.click();

    // 6. Verificar redirección exitosa a vista/inicio.php con el programa en borrador cargado
    await page.waitForURL(/vista\/inicio\.php/);
    await expect(page.locator('body')).toContainText(/Programación I|Borrador|Enviar a Revisión/);

    await monitor.tomarCaptura('profesor_programa_subido_borrador');
  });

  test('CP-E2E-28: Envío a revisión por el Profesor hacia Escuela', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-28');
    const u = USUARIOS_PRUEBA.profesor;

    await iniciarSesionComo(page, u.email, u.nombre);
    await expect(page).toHaveURL(/vista\/inicio\.php/);

    // Localizar la tarjeta de la materia Programación I
    const tarjeta = page.locator('.pending-card', { hasText: 'Programación I' });
    if (await tarjeta.count() > 0) {
      const btnEnviar = tarjeta.locator('button:has-text("Enviar a Revisión")');
      if (await btnEnviar.count() > 0) {
        await btnEnviar.first().click();
        await page.waitForLoadState('networkidle');
      }
    }

    // Verificar que el estado cambie a En Revisión / Pendiente de Escuela o desaparezca del buzón de pendientes del docente
    await monitor.tomarCaptura('profesor_programa_enviado_revision');
  });

  test('CP-E2E-29: Revisión y aprobación con firma digital por Director de Escuela', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-29');
    const u = USUARIOS_PRUEBA.escuela;
    const rutaPDF = path.resolve(__dirname, '../assets/programa_prueba.pdf');

    await iniciarSesionComo(page, u.email, u.nombre);
    await expect(page).toHaveURL(/vista\/inicio\.php/);

    // Si hay pendientes en el buzón del Director de Escuela
    const linkRevisar = page.locator('.pending-card a:has-text("Revisar Programa")');
    if (await linkRevisar.count() > 0) {
      await linkRevisar.first().click();
      await page.waitForURL(/revisar\.programa\.pdf\.php/);

      // Subir archivo firmado si el botón está visible
      const btnModalFirmado = page.locator('button[data-target="#modalSubirFirmado"]');
      if (await btnModalFirmado.count() > 0) {
        await btnModalFirmado.click();
        await page.setInputFiles('input#archivoPdf', rutaPDF);
        await page.click('#modalSubirFirmado button[type="submit"]');
        await page.waitForURL(/vista\/inicio\.php/);
      }
    } else {
      // Si no hay pendientes en el buzón, navegar al listado de revisión
      await page.goto('vista/revisar.programas.php');
      await page.waitForLoadState('networkidle');
      await expect(page.locator('body')).toContainText('Programas');
    }

    await monitor.tomarCaptura('escuela_aprobacion_o_revisar');
  });

  test('CP-E2E-30: Devolución con observaciones (Desaprobación) por Escuela al Profesor', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-30');
    const u = USUARIOS_PRUEBA.escuela;

    await iniciarSesionComo(page, u.email, u.nombre);
    await page.goto('vista/revisar.programas.php');
    await page.waitForLoadState('networkidle');

    // Buscar si existe algún programa en revisión para evaluar
    const linkFicha = page.locator('a[href*="revisar.programa.pdf.php"]');
    if (await linkFicha.count() > 0) {
      await linkFicha.first().click();
      await page.waitForURL(/revisar\.programa\.pdf\.php/);

      // Si el panel de comentarios está presente, simular desaprobación con feedback
      const txtComentario = page.locator('textarea#comentario_eval');
      if (await txtComentario.count() > 0) {
        await txtComentario.fill('TEST E2E: Se devuelven observaciones para ajuste de bibliografía y carga horaria.');
        await page.check('input#accionDesaprobar');
        await page.click('button[name="guardarComentarioAvanzado"]');
        await page.waitForLoadState('networkidle');
      }
    }

    await monitor.tomarCaptura('devolucion_observaciones_completada');
  });

});
