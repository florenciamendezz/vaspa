// @ts-check
const { execSync } = require('child_process');
const path = require('path');

const PHP_BIN = 'c:\\xampp\\php\\php.exe';

/**
 * Asegura la existencia de las asignaturas base 0174 y 1108 requeridas para los circuitos de prueba,
 * basándose fielmente en scriptBD/backup_bdgef_vaspa_manual.sql.
 */
function asegurarAsignaturasBase() {
  try {
    const scriptPath = path.resolve(__dirname, 'seed_base.php');
    const res = execSync(`"${PHP_BIN}" "${scriptPath}"`, { encoding: 'utf-8' });
    return res.includes('OK_SEED_BASE');
  } catch (e) {
    console.error('Error al asegurar asignaturas base:', e);
    return false;
  }
}

/**
 * Limpia registros creados exclusivamente por los tests con prefijo TEST_E2E_
 */
function limpiarDatosPruebaAislados() {
  try {
    const scriptPath = path.resolve(__dirname, 'clean_test.php');
    const res = execSync(`"${PHP_BIN}" "${scriptPath}"`, { encoding: 'utf-8' });
    return res.includes('OK_CLEAN_TEST');
  } catch (e) {
    console.error('Error al limpiar datos de prueba:', e);
    return false;
  }
}

module.exports = {
  asegurarAsignaturasBase,
  limpiarDatosPruebaAislados,
};
