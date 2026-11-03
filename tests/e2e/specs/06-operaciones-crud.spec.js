// @ts-check
const { test, expect } = require('@playwright/test');
const { USUARIOS_PRUEBA, iniciarSesionComo } = require('../helpers/auth.helper');
const { crearMonitor } = require('../helpers/logger.helper');
const { asegurarAsignaturasBase, limpiarDatosPruebaAislados } = require('../helpers/db.helper');

test.describe('Módulo 06: Operaciones CRUD y Validaciones de Formularios', () => {

  test.beforeAll(() => {
    asegurarAsignaturasBase();
  });

  test.afterAll(() => {
    limpiarDatosPruebaAislados();
  });

  test.afterEach(async ({ page }) => {
    try {
      await page.goto('app/salir.php');
      await page.waitForLoadState('networkidle');
    } catch (e) {
      // Ignorar
    }
  });

  test('CP-E2E-17: Alta de carrera con datos válidos', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-17');
    const u = USUARIOS_PRUEBA.vinculacion;

    await iniciarSesionComo(page, u.email, u.nombre);
    await page.goto('vista/carrera.crear.php');
    await page.waitForLoadState('networkidle');

    await expect(page.locator('h3')).toContainText('Crear Carrera');

    // Completar formulario con datos aislados de prueba
    await page.fill('input#inputCodigo', '990');
    await page.fill('input#inputNombre', 'TEST_E2E_Carrera_Sistemas');

    await monitor.tomarCaptura('formulario_carrera_completo');

    // Enviar formulario
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Debe procesar y mostrar confirmación o listado
    await expect(page).toHaveURL(/carrera\.crear\.procesar\.php|carreras\.php/);
    await expect(page.locator('body')).toContainText(/éxito|correctamente|Guardado|Carrera/i);

    await monitor.tomarCaptura('carrera_creada_exito');
  });

  test('CP-E2E-18: Validación de campos obligatorios y rangos numéricos en Carrera', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-18');
    const u = USUARIOS_PRUEBA.vinculacion;

    await iniciarSesionComo(page, u.email, u.nombre);
    await page.goto('vista/carrera.crear.php');
    await page.waitForLoadState('networkidle');

    // 1. Verificar restricciones en HTML
    const inputCodigo = page.locator('input#inputCodigo');
    const inputNombre = page.locator('input#inputNombre');

    await expect(inputCodigo).toHaveAttribute('required', '');
    await expect(inputCodigo).toHaveAttribute('min', '001');
    await expect(inputCodigo).toHaveAttribute('max', '999');
    await expect(inputNombre).toHaveAttribute('required', '');

    // 2. Probar envío con código fuera de rango
    await inputCodigo.fill('000');
    await inputNombre.fill('');

    // Disparar validación nativa HTML5
    const codigoValido = await inputCodigo.evaluate((el) => /** @type {HTMLInputElement} */ (el).checkValidity());
    const nombreValido = await inputNombre.evaluate((el) => /** @type {HTMLInputElement} */ (el).checkValidity());

    expect(codigoValido).toBe(false);
    expect(nombreValido).toBe(false);

    await monitor.tomarCaptura('validacion_html5_carrera_bloqueada');
  });

  test('CP-E2E-19: Creación de nuevo Plan de Estudio para Carrera existente', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-19');
    const u = USUARIOS_PRUEBA.vinculacion;

    await iniciarSesionComo(page, u.email, u.nombre);
    await page.goto('vista/plan.crear.php?id=016');
    await page.waitForLoadState('networkidle');

    await expect(page.locator('h3')).toContainText('Crear Plan de Estudio');

    // Completar formulario con código único aislado
    await page.fill('input#inputCodigoPlan', '016PT');
    await page.fill('input#inputAnioInicio', '2026');

    await monitor.tomarCaptura('formulario_plan_completo');

    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    await expect(page).toHaveURL(/plan\.crear\.procesar\.php|carrera\.verPlanes\.php/);
    await expect(page.locator('body')).toContainText(/éxito|correctamente|016PT|Plan/i);

    await monitor.tomarCaptura('plan_creado_exito');
  });

  test('CP-E2E-20: Visualización y editor de asignaturas de un Plan de Estudio', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-20');
    const u = USUARIOS_PRUEBA.vinculacion;

    await iniciarSesionComo(page, u.email, u.nombre);
    await page.goto('vista/plan.asignaturas.php?id=016P9&idCarrera=016');
    await page.waitForLoadState('networkidle');

    // Verificar que la pantalla cargue el catálogo y panel de asignaturas del plan
    await expect(page.locator('body')).toContainText('Asignaturas del Plan');
    const contenedor = page.locator('#contenedorTabla');
    await expect(contenedor).toBeVisible();

    await monitor.tomarCaptura('editor_asignaturas_plan');
  });

  test('CP-E2E-21: Consulta y administración de matriz de correlatividades', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-21');
    const u = USUARIOS_PRUEBA.vinculacion;

    await iniciarSesionComo(page, u.email, u.nombre);
    await page.goto('vista/asignaturas.correlativas.php?idAsignatura=0174&idPlan=016P9');
    await page.waitForLoadState('networkidle');

    // Verificar carga de la pantalla de correlatividades
    await expect(page.locator('body')).toContainText('Correlatividades');
    const contenedor = page.locator('#contenedorTablaCorrelativas, #formNuevaCorrelativa');
    await expect(contenedor.first()).toBeVisible();

    await monitor.tomarCaptura('matriz_correlatividades');
  });

  test('CP-E2E-22: Alta de usuario de prueba aislado en app/usuarios.php', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-22');
    const u = USUARIOS_PRUEBA.admin;

    await iniciarSesionComo(page, u.email, u.nombre);
    await page.goto('app/usuario.crear.php');
    await page.waitForLoadState('networkidle');

    await page.fill('input#inputNombre', 'TEST_E2E_Usuario');
    await page.fill('input#inputMail', 'testuserqa@uarg.unpa.edu.ar');

    // Seleccionar radio de rol Administrador con force: true por custom control de Bootstrap
    const radioAdmin = page.locator('input[name="rol"][value="8"], input[name="rol"]');
    await radioAdmin.first().check({ force: true });

    await monitor.tomarCaptura('formulario_usuario_completo');

    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    await expect(page).toHaveURL(/usuario\.crear\.procesar\.php|usuarios\.php/);
    await expect(page.locator('body')).toContainText(/éxito|correctamente|Usuario/i);

    await monitor.tomarCaptura('usuario_creado_confirmado');
  });

  test('CP-E2E-23: Validación de formato de correo institucional (@uarg.unpa.edu.ar) en Usuarios', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-23');
    const u = USUARIOS_PRUEBA.admin;

    await iniciarSesionComo(page, u.email, u.nombre);
    await page.goto('app/usuario.crear.php');
    await page.waitForLoadState('networkidle');

    const inputMail = page.locator('input#inputMail');
    await expect(inputMail).toHaveAttribute('pattern', '^[a-z]+@uarg.unpa.edu.ar$');

    // Ingresar correo comercial inválido
    await inputMail.fill('usuario_invalido@gmail.com');
    const esValido = await inputMail.evaluate((el) => /** @type {HTMLInputElement} */ (el).checkValidity());
    expect(esValido).toBe(false);

    // Ingresar correo institucional correcto
    await inputMail.fill('valido@uarg.unpa.edu.ar');
    const esValidoInst = await inputMail.evaluate((el) => /** @type {HTMLInputElement} */ (el).checkValidity());
    expect(esValidoInst).toBe(true);

    await monitor.tomarCaptura('validacion_regex_correo_institucional');
  });

  test('CP-E2E-24: Validación de duplicidad en alta de usuario existente', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-24');
    const u = USUARIOS_PRUEBA.admin;

    await iniciarSesionComo(page, u.email, u.nombre);
    await page.goto('app/usuario.crear.php');
    await page.waitForLoadState('networkidle');

    // Intentar registrar un usuario con email que ya existe en la base de datos (cumple regex y ya existe)
    await page.fill('input#inputNombre', 'Administrador Duplicado');
    await page.fill('input#inputMail', 'cfretes@uarg.unpa.edu.ar');
    const radioAdmin = page.locator('input[name="rol"][value="8"], input[name="rol"]');
    await radioAdmin.first().check({ force: true });

    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Debe advertir colisión o rechazo por duplicidad
    await expect(page.locator('body')).toContainText(/existe|duplicado|error|no pudo|atención/i);

    await monitor.tomarCaptura('usuario_duplicado_rechazado');
  });

  test('CP-E2E-25: Alta de docente de prueba en vista/profesores.php', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-25');
    const u = USUARIOS_PRUEBA.admin;

    await iniciarSesionComo(page, u.email, u.nombre);
    await page.goto('vista/profesor.crear.php');
    await page.waitForLoadState('networkidle');

    await expect(page.locator('h3')).toContainText('Profesor');

    await page.fill('input#inputApellidoProfesor', 'DocenteTest');
    await page.fill('input#inputNombreProfesor', 'QA');
    await page.fill('input#inputEmailProfesor', 'docenteqa@uarg.unpa.edu.ar');
    await page.locator('input#esResponsableSI').check({ force: true });

    await monitor.tomarCaptura('formulario_profesor_completo');

    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    await expect(page).toHaveURL(/profesor\.crear\.procesar\.php|profesores\.php/);
    await expect(page.locator('body')).toContainText(/éxito|correctamente|Profesor|DocenteTest/i);

    await monitor.tomarCaptura('profesor_creado_confirmado');
  });

  test('CP-E2E-26: Validación de restricción Solo_Texto en nombres de docente', async ({ page }) => {
    const monitor = crearMonitor(page, 'CP-E2E-26');
    const u = USUARIOS_PRUEBA.admin;

    await iniciarSesionComo(page, u.email, u.nombre);
    await page.goto('vista/profesor.crear.php');
    await page.waitForLoadState('networkidle');

    const inputApellido = page.locator('input#inputApellidoProfesor');

    // Verificar presencia de la función onkeypress Solo_Texto
    const onkeypressAttr = await inputApellido.getAttribute('onkeypress');
    expect(onkeypressAttr).toContain('Solo_Texto');

    // Verificar pattern que prohíbe dígitos
    const pattern = await inputApellido.getAttribute('pattern');
    expect(pattern).toBeTruthy();

    await inputApellido.fill('Perez123');
    const esValido = await inputApellido.evaluate((el) => /** @type {HTMLInputElement} */ (el).checkValidity());
    expect(esValido).toBe(false);

    await monitor.tomarCaptura('validacion_solo_texto_profesor');
  });

});
