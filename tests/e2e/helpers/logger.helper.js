// @ts-check
const path = require('path');
const fs = require('fs');

/**
 * Helper para registrar y recolectar logs de consola, errores de JS y respuestas HTTP con error.
 */
class MonitorPagina {
  /**
   * @param {import('@playwright/test').Page} page
   * @param {string} nombreCaso
   */
  constructor(page, nombreCaso) {
    this.page = page;
    this.nombreCaso = nombreCaso;
    this.logsConsola = [];
    this.erroresJS = [];
    this.respuestasFallidas = [];

    // Monitoreo de logs de consola
    this.page.on('console', msg => {
      const tipo = msg.type();
      const texto = msg.text();
      if (tipo === 'error' || tipo === 'warning') {
        this.logsConsola.push({ tipo, texto, timestamp: new Date().toISOString() });
      }
    });

    // Monitoreo de errores no capturados en la página
    this.page.on('pageerror', error => {
      this.erroresJS.push({
        mensaje: error.message,
        stack: error.stack,
        timestamp: new Date().toISOString(),
      });
    });

    // Monitoreo de respuestas HTTP con error (4xx y 5xx)
    this.page.on('response', response => {
      const status = response.status();
      const url = response.url();
      if (status >= 400) {
        this.respuestasFallidas.push({
          url,
          status,
          statusText: response.statusText(),
          timestamp: new Date().toISOString(),
        });
      }
    });

    // Aceptación automática de diálogos JavaScript estándar (alert, confirm, prompt)
    this.page.on('dialog', async dialog => {
      // Registrar mensaje de diálogo
      this.logsConsola.push({
        tipo: 'dialog',
        texto: `Diálogo [${dialog.type()}]: ${dialog.message()}`,
        timestamp: new Date().toISOString(),
      });
      await dialog.accept();
    });
  }

  /**
   * Toma una captura de pantalla guardándola en la carpeta de capturas
   * @param {string} sufijo
   * @returns {Promise<string>} ruta relativa del archivo guardado
   */
  async tomarCaptura(sufijo) {
    const screenshotsDir = path.resolve(__dirname, '../screenshots');
    if (!fs.existsSync(screenshotsDir)) {
      fs.mkdirSync(screenshotsDir, { recursive: true });
    }
    const nombreArchivo = `${this.nombreCaso}_${sufijo}.png`.replace(/[^a-zA-Z0-9_\-\.]/g, '_');
    const rutaCompleta = path.join(screenshotsDir, nombreArchivo);
    await this.page.screenshot({ path: rutaCompleta, fullPage: true });
    return `tests/e2e/screenshots/${nombreArchivo}`;
  }

  /**
   * Obtiene un resumen estructurado de todas las anomalías detectadas
   */
  obtenerResumenAnomalias() {
    return {
      caso: this.nombreCaso,
      totalLogsAdvertenciaOError: this.logsConsola.length,
      totalErroresJS: this.erroresJS.length,
      totalRespuestasFallidas: this.respuestasFallidas.length,
      logsConsola: this.logsConsola,
      erroresJS: this.erroresJS,
      respuestasFallidas: this.respuestasFallidas,
    };
  }
}

/**
 * Inicializa el monitor para una página y caso específico
 * @param {import('@playwright/test').Page} page
 * @param {string} nombreCaso
 * @returns {MonitorPagina}
 */
function crearMonitor(page, nombreCaso) {
  return new MonitorPagina(page, nombreCaso);
}

module.exports = {
  MonitorPagina,
  crearMonitor,
};
