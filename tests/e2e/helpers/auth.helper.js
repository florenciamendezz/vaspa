// @ts-check
const { expect } = require('@playwright/test');

/**
 * Cuentas y roles verificados en la base de datos local bdgef_vaspa.
 * No se almacenan contraseñas en texto plano.
 */
const USUARIOS_PRUEBA = {
  admin: {
    email: 'luzmariagaraigarai@gmail.com',
    nombre: 'Administrador Eder dos Santos',
    rol: 'Administrador',
    destinoEsperado: /usuarios\.php/,
  },
  vinculacion: {
    email: 'esstefaniamendez@gmail.com',
    nombre: 'Vinculación Académica fmendez',
    rol: 'Vinculación Académica',
    destinoEsperado: /inicio\.php/,
  },
  escuela: {
    email: 'luzgarai40@gmail.com',
    nombre: 'Director de Escuela',
    rol: 'Director de Escuela',
    destinoEsperado: /inicio\.php/,
  },
  depto_cne: {
    email: 'estiloperladoaccesorios@gmail.com',
    nombre: 'Director Depto Cs Naturales y Exactas',
    rol: 'Director de Departamento',
    destinoEsperado: /inicio\.php/,
  },
  depto_cs: {
    email: 'garaiestefi@gmail.com',
    nombre: 'Directora Depto Cs Sociales',
    rol: 'Director de Departamento',
    destinoEsperado: /inicio\.php/,
  },
  profesor: {
    email: 'accesoriosperlados@gmail.com',
    nombre: 'Profesor Sandra Casas',
    rol: 'Profesor',
    destinoEsperado: /inicio\.php/,
  },
};

/**
 * Inicia sesión mediante el formulario nativo de pruebas (POST a app/index.php)
 * implementado en lib/ControlAcceso.Class.php.
 * 
 * @param {import('@playwright/test').Page} page
 * @param {string} email
 * @param {string} nombre
 */
async function iniciarSesionComo(page, email, nombre) {
  await page.goto('app/index.php');
  await page.waitForLoadState('networkidle');

  // Enviar formulario POST de bypass nativo de desarrollo
  await page.evaluate(({ emailVal, nombreVal }) => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'index.php';

    const emailInput = document.createElement('input');
    emailInput.type = 'hidden';
    emailInput.name = 'email';
    emailInput.value = emailVal;
    form.appendChild(emailInput);

    const nombreInput = document.createElement('input');
    nombreInput.type = 'hidden';
    nombreInput.name = 'nombre';
    nombreInput.value = nombreVal;
    form.appendChild(nombreInput);

    document.body.appendChild(form);
    form.submit();
  }, { emailVal: email, nombreVal: nombre });

  await page.waitForNavigation({ waitUntil: 'networkidle' });
}

/**
 * Cierra la sesión activa navegando a app/salir.php
 * @param {import('@playwright/test').Page} page
 */
async function cerrarSesion(page) {
  await page.goto('app/salir.php');
  await page.waitForLoadState('networkidle');
}

/**
 * Limpia todas las cookies de la sesión del contexto del navegador
 * @param {import('@playwright/test').BrowserContext} context
 */
async function limpiarCookiesSesion(context) {
  await context.clearCookies();
}

module.exports = {
  USUARIOS_PRUEBA,
  iniciarSesionComo,
  cerrarSesion,
  limpiarCookiesSesion,
};
