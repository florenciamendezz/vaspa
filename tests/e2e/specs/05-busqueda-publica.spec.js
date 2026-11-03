// @ts-check
const { test, expect } = require('@playwright/test');
const { crearMonitor } = require('../helpers/logger.helper');

test.describe('Módulo 05: Búsqueda y Consulta Pública (Sin Autenticación)', () => {

  test('CP-E2E-16: Consulta pública de programas por año con actualización dinámica vía AJAX', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-16');

    // Navegar al portal público sin haber iniciado sesión
    await page.goto('app/index.php');
    await page.waitForLoadState('networkidle');

    // Verificar presencia del card de búsqueda pública
    await expect(page.locator('body')).toContainText('Visualizar Programa de Asignatura');

    // Esperar a que el selector de año esté disponible
    const selectAnio = page.locator('select#selectAnio');
    await expect(selectAnio).toBeAttached();

    // Interceptar la petición AJAX de carga de carreras
    const respuestaCarrerasPromise = page.waitForResponse(response =>
      response.url().includes('visualizar.programa.cargar.carreras.php') && response.status() === 200
    );

    // Seleccionar el año actual o 2026
    const anioActual = new Date().getFullYear().toString();
    await selectAnio.selectOption(anioActual);
    await selectAnio.dispatchEvent('change');

    // Esperar respuesta AJAX
    const respCarreras = await respuestaCarrerasPromise;
    expect(respCarreras.status()).toBe(200);

    // Esperar que la red se estabilice
    await page.waitForLoadState('networkidle');

    // Verificar que el combo de carreras contenga opciones cargadas
    const opcionesCarreras = page.locator('select#selectCarrera option');
    const cantidadOpciones = await opcionesCarreras.count();
    expect(cantidadOpciones).toBeGreaterThan(0);

    // Si hay carreras cargadas, seleccionar la primera para disparar la carga de programas
    if (cantidadOpciones > 1) {
      const respuestaProgramasPromise = page.waitForResponse(response =>
        response.url().includes('tablaProgramasAsignaturas.php')
      );

      // Seleccionar primera carrera disponible (ej. valor diferente de vacío)
      const primerValorValido = await page.locator('select#selectCarrera option:nth-child(2)').getAttribute('value');
      if (primerValorValido) {
        await page.locator('select#selectCarrera').selectOption(primerValorValido);
        await page.locator('select#selectCarrera').dispatchEvent('change');

        const respProg = await respuestaProgramasPromise;
        expect(respProg.status()).toBe(200);
      }
    }

    await monitor.tomarCaptura('busqueda_publica_ajax_carreras');
  });

});
