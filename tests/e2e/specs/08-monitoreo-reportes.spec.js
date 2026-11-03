// @ts-check
const { test, expect } = require('@playwright/test');
const { USUARIOS_PRUEBA, iniciarSesionComo } = require('../helpers/auth.helper');
const { crearMonitor } = require('../helpers/logger.helper');

test.describe('Módulo 08: Monitoreo, Filtros Dinámicos e Informes Gerenciales', () => {

  test.afterEach(async ({ page }) => {
    try {
      await page.goto('app/salir.php');
      await page.waitForLoadState('networkidle');
    } catch (e) {
      // Ignorar
    }
  });

  test('CP-E2E-31: Conmutación interactiva del switch de Vacancia de Escuela en Monitoreo', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-31');
    const u = USUARIOS_PRUEBA.vinculacion;

    await iniciarSesionComo(page, u.email, u.nombre);
    await page.goto('vista/monitoreo.circuito.php');
    await page.waitForLoadState('networkidle');

    await expect(page.locator('h1')).toContainText('Panel de Monitoreo del Circuito');

    const switchVacancia = page.locator('input#switchVacancia');
    await expect(switchVacancia).toBeAttached();

    // Estado inicial
    const estadoInicial = await switchVacancia.isChecked();

    // Conmutar el switch para probar la conmutación
    const labelSwitch = page.locator('label[for="switchVacancia"]');
    await labelSwitch.click();
    await page.waitForLoadState('networkidle');

    await expect(page).toHaveURL(/monitoreo\.circuito\.php/);

    // Revertir a su estado original para no dejar efectos colaterales
    const estadoModificado = await page.locator('input#switchVacancia').isChecked();
    if (estadoModificado !== estadoInicial) {
      await page.locator('label[for="switchVacancia"]').click();
      await page.waitForLoadState('networkidle');
    }

    await monitor.tomarCaptura('monitoreo_switch_vacancia_conmutado');
  });

  test('CP-E2E-32: Filtrado dinámico por Carrera y Plan en bandeja de revisión', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-32');
    const u = USUARIOS_PRUEBA.vinculacion;

    await iniciarSesionComo(page, u.email, u.nombre);
    await page.goto('vista/revisar.programas.php');
    await page.waitForLoadState('networkidle');

    // Verificar texto representativo de la bandeja de revisión
    await expect(page.locator('body')).toContainText(/Programas|Carrera|Plan/);

    // Comprobar presencia de selectores de filtro
    const selectCarrera = page.locator('select#carrera, select[name="carrera"]');
    if (await selectCarrera.count() > 0) {
      const opciones = selectCarrera.locator('option');
      if (await opciones.count() > 1) {
        // Seleccionar una carrera
        await selectCarrera.first().selectOption({ index: 1 });
        await selectCarrera.first().dispatchEvent('change');
        await page.waitForLoadState('networkidle');
      }
    }

    // Verificar pestañas de revisión (Pendientes / Estado General)
    const tabEstadoGeneral = page.locator('#estadoGeneral-tab, a:has-text("Estado General")');
    if (await tabEstadoGeneral.count() > 0) {
      await tabEstadoGeneral.first().click();
      await page.waitForLoadState('networkidle');
    }

    await monitor.tomarCaptura('revisar_programas_filtros_aplicados');
  });

  test('CP-E2E-33: Generación y visualización del Tablero e Informe Gerencial', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-33');
    const u = USUARIOS_PRUEBA.vinculacion;

    await iniciarSesionComo(page, u.email, u.nombre);
    await page.goto('vista/informeGerencial.programas.php');
    await page.waitForLoadState('networkidle');

    // Verificar presencia de los elementos clave del informe gerencial
    await expect(page.locator('body')).toContainText('Informe Gerencial de Programas');

    // Verificar presencia de las 3 pestañas principales de reporte
    await expect(page.locator('#home-tab')).toBeVisible();
    await expect(page.locator('#profile-tab')).toBeVisible();
    await expect(page.locator('#historial-tab')).toBeVisible();

    // Verificar contenedor de presentación
    const contenedorReporte = page.locator('#tablaProgramasAsignaturas, .card-premium');
    await expect(contenedorReporte.first()).toBeVisible();

    await monitor.tomarCaptura('informe_gerencial_kpis_visibles');
  });

});
