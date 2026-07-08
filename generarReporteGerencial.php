<?php
// Extracción dinámica de las imágenes de logos en Base64 para evitar incrustar cadenas gigantes de texto
$file_path = __DIR__ . '/vista/informeGerencial.programas.php';
$logoUnpa = '';
$logo = '';

if (file_exists($file_path)) {
    $content = file_get_contents($file_path);

    // Extraer logoUnpa
    if (preg_match("/var logoUnpa\s*=\s*'([^']+)';/", $content, $matches_unpa)) {
        $logoUnpa = $matches_unpa[1];
    }

    // Extraer logo
    if (preg_match("/var logo\s*=\s*'([^']+)';/", $content, $matches_logo)) {
        $logo = $matches_logo[1];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Informe Gerencial — VASPA</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Plus+Jakarta+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (Solo para maquetación rápida y moderna de la UI del dashboard web) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Librerías para PDF y Gráficos -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <style>
        body {
            background: radial-gradient(circle at top right, #1e1b4b, #0f172a);
            min-height: 100vh;
            color: #f8fafc;
        }
        .glass-card {
            background: rgba(30, 41, 59, 0.45);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        .btn-gradient {
            background: linear-gradient(135deg, #4f46e5, #6366f1, #ec4899);
            background-size: 200% 200%;
            animation: gradient-shift 6s ease infinite;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.5);
        }
        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    </style>
</head>
<body class="font-sans antialiased flex flex-col items-center justify-center p-6">

    <!-- Contenedor Principal (Dashboard) -->
    <div class="w-full max-w-4xl glass-card rounded-3xl p-8 md:p-12 text-center relative overflow-hidden">
        <!-- Decoraciones de fondo -->
        <div class="absolute -top-12 -left-12 w-48 h-48 bg-indigo-500 rounded-full blur-3xl opacity-20 pointer-events-none"></div>
        <div class="absolute -bottom-12 -right-12 w-48 h-48 bg-pink-500 rounded-full blur-3xl opacity-20 pointer-events-none"></div>

        <!-- Encabezado de UI -->
        <div class="flex items-center justify-between mb-8 pb-6 border-b border-slate-700/50">
            <div class="flex items-center gap-3">
                <img src="<?php echo $logo; ?>" alt="VASPA" class="w-12 h-12 object-contain bg-slate-800/80 p-2 rounded-xl border border-slate-700">
                <div class="text-left">
                    <span class="text-xs font-semibold uppercase tracking-widest text-indigo-400 font-outfit">Sistema Académico</span>
                    <h2 class="text-xl font-extrabold text-white font-outfit">VASPA</h2>
                </div>
            </div>
            <img src="<?php echo $logoUnpa; ?>" alt="UNPA" class="h-10 object-contain opacity-80">
        </div>

        <!-- Título principal -->
        <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight text-white mb-4 font-outfit">
            Informe Gerencial de <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-pink-400 to-amber-300">Programas</span>
        </h1>
        <p class="text-slate-400 max-w-xl mx-auto mb-10 text-sm md:text-base leading-relaxed">
            Generador oficial de reportes de estado de planes de estudio, carga académica, distribución docente e indicadores institucionales del sistema VASPA.
        </p>

        <!-- Indicador de Descarga Automática -->
        <div class="inline-flex items-center gap-3 px-4 py-2 rounded-full bg-slate-800/60 border border-slate-700/80 mb-10 text-xs font-medium text-slate-300">
            <span class="flex h-2 w-2 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
            Generación automática en formato PDF A4 Estándar
        </div>

        <!-- Botón de acción -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
            <button id="btnGenerar" onclick="generarPDF()" class="btn-gradient px-8 py-4 rounded-2xl text-white font-bold font-outfit tracking-wide flex items-center gap-3 w-full sm:w-auto justify-center shadow-lg cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Generar y Descargar Reporte
            </button>
        </div>

        <!-- Indicador de Progreso (Oculto por defecto) -->
        <div id="progresoContainer" class="hidden mt-10 p-6 rounded-2xl bg-slate-900/50 border border-slate-800/80 max-w-md mx-auto">
            <div class="flex justify-between text-xs font-semibold text-slate-400 mb-2">
                <span id="progresoTexto">Inicializando componentes...</span>
                <span id="progresoPorcentaje">0%</span>
            </div>
            <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                <div id="progresoBarra" class="bg-indigo-500 h-full rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
        </div>
    </div>

    <!-- Contenedor oculto de Canvas para Chart.js (fuera de la pantalla para renderizar las imágenes) -->
    <div class="absolute -left-[9999px] -top-[9999px]" style="width: 800px; height: 400px;">
        <canvas id="canvasCarreras" width="800" height="400"></canvas>
        <canvas id="canvasDocentes" width="800" height="400"></canvas>
        <canvas id="canvasPlanes" width="800" height="400"></canvas>
        <canvas id="canvasCuatrimestre" width="800" height="400"></canvas>
    </div>

    <script>
        // Logotipos incrustados desde PHP
        const logoBase64 = "<?php echo $logo; ?>";
        const logoUnpaBase64 = "<?php echo $logoUnpa; ?>";

        // Al cargar la página, se dispara automáticamente la generación después de 1 segundo
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                generarPDF();
            }, 1000);
        });

        // Configuración y generación del PDF
        async function generarPDF() {
            const btn = document.getElementById('btnGenerar');
            const progresoContainer = document.getElementById('progresoContainer');
            const progresoTexto = document.getElementById('progresoTexto');
            const progresoPorcentaje = document.getElementById('progresoPorcentaje');
            const progresoBarra = document.getElementById('progresoBarra');

            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            progresoContainer.classList.remove('hidden');

            const actualizarProgreso = (texto, pct) => {
                progresoTexto.innerText = texto;
                progresoPorcentaje.innerText = pct + '%';
                progresoBarra.style.width = pct + '%';
            };

            try {
                // Paso 1: Renderizar gráficos con Chart.js (Secuencial)
                actualizarProgreso("Generando gráfico de Carreras...", 10);
                const imgCarreras = await renderChartCarreras();
                
                actualizarProgreso("Generando gráfico de Docentes...", 25);
                const imgDocentes = await renderChartDocentes();
                
                actualizarProgreso("Generando gráfico de Planes de Estudio...", 40);
                const imgPlanes = await renderChartPlanes();
                
                actualizarProgreso("Generando gráfico de Distribución Cuatrimestral...", 55);
                const imgCuatrimestres = await renderChartCuatrimestres();

                actualizarProgreso("Inicializando documento PDF...", 70);
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('p', 'mm', 'a4'); // A4: 210 x 297 mm
                const totalPages = 5;

                const fechaActual = new Date();
                const YYYY = fechaActual.getFullYear();
                const MM = String(fechaActual.getMonth() + 1).padStart(2, '0');
                const DD = String(fechaActual.getDate()).padStart(2, '0');
                const fechaString = `${DD}/${MM}/${YYYY}`;
                const horaString = fechaActual.toTimeString().split(' ')[0];

                // Función auxiliar para dibujar encabezado institucional en páginas >= 2
                const dibujarEncabezadoEstandar = (doc, modulo, pagina) => {
                    // Logos
                    if (logoBase64) doc.addImage(logoBase64, 'PNG', 20, 10, 15, 15);
                    if (logoUnpaBase64) doc.addImage(logoUnpaBase64, 'PNG', 180, 11, 10, 13);
                    
                    // Texto central
                    doc.setFont("helvetica", "bold");
                    doc.setFontSize(11);
                    doc.setTextColor(31, 41, 55); // slate-800
                    doc.text("Reporte de Estado — " + modulo, 105, 16, { align: "center" });
                    
                    doc.setFont("helvetica", "normal");
                    doc.setFontSize(8);
                    doc.setTextColor(100, 116, 139); // slate-500
                    doc.text("Generado el " + fechaString + " a las " + horaString, 105, 21, { align: "center" });
                    
                    // Separador
                    doc.setDrawColor(226, 232, 240); // slate-200
                    doc.setLineWidth(0.4);
                    doc.line(20, 27, 190, 27);
                };

                // Función auxiliar para pie de página en páginas >= 2
                const dibujarPieEstandar = (doc, pagina) => {
                    doc.setDrawColor(226, 232, 240); // slate-200
                    doc.setLineWidth(0.3);
                    doc.line(20, 280, 190, 280);

                    doc.setFont("helvetica", "normal");
                    doc.setFontSize(8);
                    doc.setTextColor(148, 163, 184); // slate-400
                    doc.text("Sistema Académico VASPA", 20, 286);
                    doc.text(`Página ${pagina} de ${totalPages}`, 190, 286, { align: "right" });
                };

                // Función auxiliar para los títulos de sección
                const dibujarTituloSeccion = (doc, titulo) => {
                    doc.setFont("helvetica", "bold");
                    doc.setFontSize(14);
                    doc.setTextColor(44, 62, 80); // Azul oscuro
                    doc.text(titulo, 20, 37);

                    // Línea bicolor abajo
                    doc.setDrawColor(226, 232, 240); // Gris
                    doc.setLineWidth(1);
                    doc.line(20, 41, 190, 41);

                    doc.setDrawColor(230, 126, 34); // Naranja
                    doc.setLineWidth(1.8);
                    doc.line(20, 41, 60, 41);
                };

                // ==========================================
                // PAGINA 1: PORTADA, INFORMACION Y KPI
                // ==========================================
                actualizarProgreso("Maquetando Portada y Métricas...", 80);
                
                // Cabecera simplificada
                if (logoBase64) doc.addImage(logoBase64, 'PNG', 20, 12, 18, 18);
                if (logoUnpaBase64) doc.addImage(logoUnpaBase64, 'PNG', 178, 13, 12, 16);
                
                doc.setFont("helvetica", "bold");
                doc.setFontSize(10);
                doc.setTextColor(71, 85, 105); // slate-600
                doc.text("INFORME ACADÉMICO GERENCIAL", 105, 22, { align: "center" });

                // Título y logotipo central
                if (logoBase64) doc.addImage(logoBase64, 'PNG', 88, 48, 34, 34);
                
                doc.setFont("helvetica", "bold");
                doc.setFontSize(32);
                doc.setTextColor(30, 41, 59); // slate-800
                doc.text("VASPA", 105, 96, { align: "center" });
                
                doc.setFont("helvetica", "normal");
                doc.setFontSize(11);
                doc.setTextColor(100, 116, 139); // slate-500
                doc.text("Planificación Académica e Indicadores de Estado", 105, 102, { align: "center" });

                // Caja: Información General
                doc.setDrawColor(99, 102, 241); // indigo-500
                doc.setLineWidth(1.2);
                doc.line(20, 114, 20, 144); // Borde izquierdo

                doc.setFillColor(248, 250, 252); // slate-50
                doc.rect(20.6, 114, 169.4, 30, "F");

                doc.setFont("helvetica", "bold");
                doc.setFontSize(9);
                doc.setTextColor(51, 65, 85);
                
                doc.text("Estado del Sistema:", 26, 121);
                doc.text("Período Evaluado:", 26, 129);
                doc.text("Descripción General:", 26, 137);

                doc.setFont("helvetica", "normal");
                doc.setTextColor(71, 85, 105);
                doc.text("Producción / Activo", 62, 121);
                doc.text("Ciclo Lectivo 2026", 62, 129);
                doc.text("Control y auditoría de carreras, planes de estudio y docentes.", 62, 137);

                // Sección: Métricas Clave
                doc.setFont("helvetica", "bold");
                doc.setFontSize(13);
                doc.setTextColor(30, 41, 59);
                doc.text("Métricas Clave del Sistema", 20, 158);

                // Subrayado con acento naranja
                doc.setDrawColor(226, 232, 240);
                doc.setLineWidth(0.8);
                doc.line(20, 162, 190, 162);
                doc.setDrawColor(230, 126, 34); // naranja
                doc.setLineWidth(1.5);
                doc.line(20, 162, 50, 162);

                // Grid 2x3 de tarjetas de métricas
                const metricas = [
                    { label: "CARRERAS ACTIVAS", val: "6", color: [16, 185, 129] },    // Emerald
                    { label: "PLANES DE ESTUDIO", val: "14", color: [59, 130, 246] },  // Blue
                    { label: "ASIGNATURAS", val: "184", color: [99, 102, 241] },       // Indigo
                    { label: "DOCENTES ASIGNADOS", val: "58", color: [16, 185, 129] }, // Emerald
                    { label: "ASIGNATURAS SIN PROF.", val: "12", color: [239, 68, 68] }, // Red (Alerta)
                    { label: "PLANES EN TRANSICIÓN", val: "3", color: [245, 158, 11] }  // Amber
                ];

                const cardW = 52;
                const cardH = 26;
                const startX = 20;
                const gapX = 7;
                
                // Fila 1 (Y = 168)
                for (let i = 0; i < 3; i++) {
                    const m = metricas[i];
                    const x = startX + i * (cardW + gapX);
                    const y = 168;

                    // Fondo y contorno
                    doc.setFillColor(255, 255, 255);
                    doc.setDrawColor(226, 232, 240);
                    doc.setLineWidth(0.25);
                    doc.roundedRect(x, y, cardW, cardH, 1.5, 1.5, "FD");

                    // Borde izquierdo grueso
                    doc.setFillColor(m.color[0], m.color[1], m.color[2]);
                    doc.rect(x, y, 2, cardH, "F");

                    // Contenido
                    doc.setFont("helvetica", "bold");
                    doc.setFontSize(6.5);
                    doc.setTextColor(100, 116, 139);
                    doc.text(m.label, x + 5, y + 7);

                    doc.setFont("helvetica", "bold");
                    doc.setFontSize(16);
                    doc.setTextColor(30, 41, 59);
                    doc.text(m.val, x + cardW - 5, y + 19, { align: "right" });
                }

                // Fila 2 (Y = 200)
                for (let i = 0; i < 3; i++) {
                    const m = metricas[i + 3];
                    const x = startX + i * (cardW + gapX);
                    const y = 200;

                    // Fondo y contorno
                    doc.setFillColor(255, 255, 255);
                    doc.setDrawColor(226, 232, 240);
                    doc.setLineWidth(0.25);
                    doc.roundedRect(x, y, cardW, cardH, 1.5, 1.5, "FD");

                    // Borde izquierdo grueso
                    doc.setFillColor(m.color[0], m.color[1], m.color[2]);
                    doc.rect(x, y, 2, cardH, "F");

                    // Contenido
                    doc.setFont("helvetica", "bold");
                    doc.setFontSize(6.5);
                    doc.setTextColor(100, 116, 139);
                    doc.text(m.label, x + 5, y + 7);

                    doc.setFont("helvetica", "bold");
                    doc.setFontSize(16);
                    doc.setTextColor(30, 41, 59);
                    doc.text(m.val, x + cardW - 5, y + 19, { align: "right" });
                }

                // Pie de página oficial de Portada
                doc.setDrawColor(226, 232, 240);
                doc.setLineWidth(0.3);
                doc.line(20, 275, 190, 275);
                doc.setFont("helvetica", "normal");
                doc.setFontSize(8);
                doc.setTextColor(148, 163, 184);
                doc.text("Auditoría General Académica — Documento Confidencial", 105, 282, { align: "center" });

                // ==========================================
                // PAGINA 2: ASIGNATURAS POR CARRERA
                // ==========================================
                actualizarProgreso("Escribiendo Sección: Asignaturas por Carrera...", 85);
                doc.addPage();
                dibujarEncabezadoEstandar(doc, "Asignaturas por Carrera", 2);
                dibujarTituloSeccion(doc, "Cantidad de Asignaturas por Carrera");

                // Gráfico
                doc.setFont("helvetica", "bold");
                doc.setFontSize(8.5);
                doc.setTextColor(71, 85, 105);
                doc.text("Gráfico: Asignaturas Totales por Programas Vigentes", 105, 48, { align: "center" });
                doc.addImage(imgCarreras, 'PNG', 45, 52, 120, 60);

                // Tabla
                doc.autoTable({
                    startY: 118,
                    margin: { left: 20, right: 20 },
                    theme: 'striped',
                    head: [['Carrera', 'Plan Vigente', 'Total Asignaturas', 'Asignaturas Activas']],
                    body: [
                        ['Licenciatura en Sistemas', 'Plan 2023', '32', '32'],
                        ['Licenciatura en Turismo', 'Plan 2018', '28', '28'],
                        ['Tecnicatura Universitaria en Turismo', 'Plan 2018', '22', '22'],
                        ['Licenciatura en Letras', 'Plan 2019', '26', '24'],
                        ['Licenciatura en Enfermería', 'Plan 2020', '38', '36'],
                        ['Licenciatura en Administración', 'Plan 2021', '38', '38']
                    ],
                    headStyles: {
                        fillColor: [44, 62, 80],
                        textColor: [255, 255, 255],
                        fontStyle: 'bold',
                        halign: 'left',
                        fontSize: 8.5
                    },
                    bodyStyles: {
                        textColor: [51, 51, 51],
                        fontSize: 8
                    },
                    alternateRowStyles: {
                        fillColor: [248, 249, 250]
                    },
                    columnStyles: {
                        2: { halign: 'right' },
                        3: { halign: 'right' }
                    },
                    didParseCell: function(data) {
                        // Resaltar celdas numéricas positivas en verde
                        if (data.section === 'body' && (data.column.index === 2 || data.column.index === 3)) {
                            data.cell.styles.textColor = [39, 174, 96]; // #27ae60
                            data.cell.styles.fontStyle = 'bold';
                        }
                    }
                });
                dibujarPieEstandar(doc, 2);

                // ==========================================
                // PAGINA 3: DISTRIBUCIÓN DE CARGA DOCENTE
                // ==========================================
                actualizarProgreso("Escribiendo Sección: Distribución de Carga Docente...", 90);
                doc.addPage();
                dibujarEncabezadoEstandar(doc, "Distribución de Carga Docente", 3);
                dibujarTituloSeccion(doc, "Carga de Asignaturas por Profesor (Top 10)");

                // Gráfico
                doc.setFont("helvetica", "bold");
                doc.setFontSize(8.5);
                doc.setTextColor(71, 85, 105);
                doc.text("Gráfico: Profesores con Mayor Asignación Académica", 105, 48, { align: "center" });
                doc.addImage(imgDocentes, 'PNG', 45, 52, 120, 60);

                // Tabla
                doc.autoTable({
                    startY: 118,
                    margin: { left: 20, right: 20 },
                    theme: 'striped',
                    head: [['Docente / Profesor', 'Asignaturas Asignadas', 'Carreras Involucradas']],
                    body: [
                        ['Dr. Gómez, Carlos', '5', 'Lic. en Sistemas, Lic. en Administración'],
                        ['Dra. Fernández, Ana', '4', 'Lic. en Letras'],
                        ['Mg. López, María', '4', 'Lic. en Turismo, Tec. en Turismo'],
                        ['Ing. Rodríguez, Juan', '3', 'Lic. en Sistemas'],
                        ['Lic. Martínez, Sofía', '3', 'Lic. en Sistemas, Lic. en Turismo'],
                        ['Mg. Pérez, Luis', '3', 'Lic. en Administración'],
                        ['Dr. Sánchez, Jorge', '3', 'Lic. en Letras, Lic. en Enfermería'],
                        ['Dra. Diaz, Laura', '2', 'Lic. en Enfermería'],
                        ['Mg. Silva, Pedro', '2', 'Lic. en Sistemas, Lic. en Turismo'],
                        ['Ing. Castro, Marta', '2', 'Lic. en Sistemas']
                    ],
                    headStyles: {
                        fillColor: [44, 62, 80],
                        textColor: [255, 255, 255],
                        fontStyle: 'bold',
                        halign: 'left',
                        fontSize: 8.5
                    },
                    bodyStyles: {
                        textColor: [51, 51, 51],
                        fontSize: 8
                    },
                    alternateRowStyles: {
                        fillColor: [248, 249, 250]
                    },
                    columnStyles: {
                        1: { halign: 'right' }
                    },
                    didParseCell: function(data) {
                        if (data.section === 'body' && data.column.index === 1) {
                            data.cell.styles.textColor = [39, 174, 96]; // Verde
                            data.cell.styles.fontStyle = 'bold';
                        }
                    }
                });
                dibujarPieEstandar(doc, 3);

                // ==========================================
                // PAGINA 4: ESTADO DE PLANES DE ESTUDIO
                // ==========================================
                actualizarProgreso("Escribiendo Sección: Planes de Estudio...", 95);
                doc.addPage();
                dibujarEncabezadoEstandar(doc, "Estado de Planes de Estudio", 4);
                dibujarTituloSeccion(doc, "Consolidado de Planes y Estado");

                // Gráfico
                doc.setFont("helvetica", "bold");
                doc.setFontSize(8.5);
                doc.setTextColor(71, 85, 105);
                doc.text("Gráfico: Distribución de Planes Vigentes, Transición e Inactivos", 105, 48, { align: "center" });
                doc.addImage(imgPlanes, 'PNG', 55, 52, 100, 60);

                // Tabla
                doc.autoTable({
                    startY: 118,
                    margin: { left: 20, right: 20 },
                    theme: 'striped',
                    head: [['Carrera', 'Plan', 'Año Inicio', 'Estado', 'Asignaturas']],
                    body: [
                        ['Licenciatura en Sistemas', 'Plan 2023', '2023', 'Vigente', '32'],
                        ['Licenciatura en Sistemas', 'Plan 2015', '2015', 'Inactivo', '30'],
                        ['Licenciatura en Turismo', 'Plan 2018', '2018', 'Vigente', '28'],
                        ['Tecnicatura Universitaria en Turismo', 'Plan 2018', '2018', 'Vigente', '22'],
                        ['Licenciatura en Letras', 'Plan 2019', '2019', 'Vigente', '26'],
                        ['Licenciatura en Letras', 'Plan 2008', '2008', 'Inactivo', '25'],
                        ['Licenciatura en Enfermería', 'Plan 2020', '2020', 'Vigente', '38'],
                        ['Licenciatura en Enfermería', 'Plan 2012', '2012', 'En Transición', '35'],
                        ['Licenciatura en Administración', 'Plan 2021', '2021', 'Vigente', '38'],
                        ['Licenciatura en Administración', 'Plan 2010', '2010', 'Inactivo', '36']
                    ],
                    headStyles: {
                        fillColor: [44, 62, 80],
                        textColor: [255, 255, 255],
                        fontStyle: 'bold',
                        halign: 'left',
                        fontSize: 8.5
                    },
                    bodyStyles: {
                        textColor: [51, 51, 51],
                        fontSize: 8
                    },
                    alternateRowStyles: {
                        fillColor: [248, 249, 250]
                    },
                    columnStyles: {
                        2: { halign: 'center' },
                        4: { halign: 'right' }
                    },
                    didParseCell: function(data) {
                        if (data.section === 'body' && data.column.index === 3) {
                            const val = data.cell.raw.toString().trim();
                            if (val === 'Vigente') {
                                data.cell.styles.textColor = [39, 174, 96]; // Verde
                                data.cell.styles.fontStyle = 'bold';
                            } else if (val === 'Inactivo') {
                                data.cell.styles.textColor = [120, 120, 120]; // Gris
                            } else if (val === 'En Transición') {
                                data.cell.styles.textColor = [230, 126, 34]; // Naranja
                                data.cell.styles.fontStyle = 'bold';
                            }
                        }
                    }
                });
                dibujarPieEstandar(doc, 4);

                // ==========================================
                // PAGINA 5: ASIGNATURAS POR CUATRIMESTRE
                // ==========================================
                actualizarProgreso("Escribiendo Sección: Carga Temporal...", 98);
                doc.addPage();
                dibujarEncabezadoEstandar(doc, "Distribución Temporal", 5);
                dibujarTituloSeccion(doc, "Asignaturas por Año Curricular y Régimen");

                // Gráfico
                doc.setFont("helvetica", "bold");
                doc.setFontSize(8.5);
                doc.setTextColor(71, 85, 105);
                doc.text("Gráfico: Distribución Cuatrimestral por Año de Carrera", 105, 48, { align: "center" });
                doc.addImage(imgCuatrimestres, 'PNG', 45, 52, 120, 60);

                // Tabla
                doc.autoTable({
                    startY: 118,
                    margin: { left: 20, right: 20 },
                    theme: 'striped',
                    head: [['Año Curricular', 'Régimen de Cursado', 'Cantidad de Asignaturas']],
                    body: [
                        ['1er Año', '1er Cuatrimestre', '8'],
                        ['1er Año', '2do Cuatrimestre', '8'],
                        ['1er Año', 'Anual', '2'],
                        ['2do Año', '1er Cuatrimestre', '7'],
                        ['2do Año', '2do Cuatrimestre', '7'],
                        ['2do Año', 'Anual', '1'],
                        ['3er Año', '1er Cuatrimestre', '6'],
                        ['3er Año', '2do Cuatrimestre', '6'],
                        ['4to Año', '1er Cuatrimestre', '5'],
                        ['4to Año', '2do Cuatrimestre', '6'],
                        ['5to Año', '1er Cuatrimestre', '4'],
                        ['5to Año', '2do Cuatrimestre', '4']
                    ],
                    headStyles: {
                        fillColor: [44, 62, 80],
                        textColor: [255, 255, 255],
                        fontStyle: 'bold',
                        halign: 'left',
                        fontSize: 8.5
                    },
                    bodyStyles: {
                        textColor: [51, 51, 51],
                        fontSize: 8
                    },
                    alternateRowStyles: {
                        fillColor: [248, 249, 250]
                    },
                    columnStyles: {
                        2: { halign: 'right' }
                    },
                    didParseCell: function(data) {
                        if (data.section === 'body' && data.column.index === 2) {
                            data.cell.styles.textColor = [39, 174, 96]; // Verde
                            data.cell.styles.fontStyle = 'bold';
                        }
                    }
                });
                dibujarPieEstandar(doc, 5);

                // Guardar PDF
                actualizarProgreso("Generando archivo final...", 100);
                const nombrePDF = `reporte-vaspa-${YYYY}-${MM}-${DD}.pdf`;
                doc.save(nombrePDF);

                // Ocultar modal de progreso lentamente
                setTimeout(() => {
                    progresoContainer.classList.add('hidden');
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                }, 1500);

            } catch (err) {
                console.error(err);
                alert("Ocurrió un error al generar el reporte PDF: " + err.message);
                progresoContainer.classList.add('hidden');
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }

        // ==========================================
        // FUNCIONES RENDER CHART (CHART.JS)
        // ==========================================

        function renderChartCarreras() {
            return new Promise((resolve) => {
                const ctx = document.getElementById('canvasCarreras').getContext('2d');
                const chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Lic. Sistemas', 'Lic. Turismo', 'T.U. Turismo', 'Lic. Letras', 'Lic. Enfermería', 'Lic. Admin.'],
                        datasets: [{
                            label: 'Asignaturas',
                            data: [32, 28, 22, 26, 38, 38],
                            backgroundColor: '#5b9bd5',
                            borderColor: '#2c3e50',
                            borderWidth: 1.5,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        indexAxis: 'y', // Barras horizontales
                        animation: false,
                        responsive: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            x: {
                                grid: { color: '#f1f5f9' },
                                ticks: { font: { family: 'Plus Jakarta Sans', size: 9 } }
                            },
                            y: {
                                grid: { display: false },
                                ticks: { font: { family: 'Plus Jakarta Sans', size: 9, weight: 'bold' } }
                            }
                        }
                    }
                });
                
                // Asegurar renderizado estático
                setTimeout(() => {
                    const img = chart.toBase64Image();
                    chart.destroy();
                    resolve(img);
                }, 150);
            });
        }

        function renderChartDocentes() {
            return new Promise((resolve) => {
                const ctx = document.getElementById('canvasDocentes').getContext('2d');
                const chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Gómez', 'Fernández', 'López', 'Rodríguez', 'Martínez', 'Pérez', 'Sánchez', 'Diaz', 'Silva', 'Castro'],
                        datasets: [{
                            label: 'Carga Docente',
                            data: [5, 4, 4, 3, 3, 3, 3, 2, 2, 2],
                            backgroundColor: '#70ad47',
                            borderColor: '#2c3e50',
                            borderWidth: 1.5,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        animation: false,
                        responsive: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                grid: { color: '#f1f5f9' },
                                ticks: { font: { family: 'Plus Jakarta Sans', size: 9 } }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { font: { family: 'Plus Jakarta Sans', size: 8.5, weight: 'bold' } }
                            }
                        }
                    }
                });

                setTimeout(() => {
                    const img = chart.toBase64Image();
                    chart.destroy();
                    resolve(img);
                }, 150);
            });
        }

        function renderChartPlanes() {
            return new Promise((resolve) => {
                const ctx = document.getElementById('canvasPlanes').getContext('2d');
                const chart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Vigentes', 'En Transición', 'Inactivos'],
                        datasets: [{
                            data: [14, 3, 5],
                            backgroundColor: ['#10b981', '#ffc000', '#ef4444'],
                            borderColor: '#ffffff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        animation: false,
                        responsive: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'right',
                                labels: {
                                    font: { family: 'Plus Jakarta Sans', size: 11, weight: 'bold' },
                                    boxWidth: 15,
                                    padding: 15
                                }
                            }
                        }
                    }
                });

                setTimeout(() => {
                    const img = chart.toBase64Image();
                    chart.destroy();
                    resolve(img);
                }, 150);
            });
        }

        function renderChartCuatrimestres() {
            return new Promise((resolve) => {
                const ctx = document.getElementById('canvasCuatrimestre').getContext('2d');
                const chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['1er Año', '2do Año', '3er Año', '4to Año', '5to Año'],
                        datasets: [
                            {
                                label: '1er Cuatrimestre',
                                data: [8, 7, 6, 5, 4],
                                backgroundColor: '#5b9bd5',
                                borderRadius: 3
                            },
                            {
                                label: '2do Cuatrimestre',
                                data: [8, 7, 6, 6, 4],
                                backgroundColor: '#70ad47',
                                borderRadius: 3
                            },
                            {
                                label: 'Anual',
                                data: [2, 1, 0, 0, 0],
                                backgroundColor: '#ffc000',
                                borderRadius: 3
                            }
                        ]
                    },
                    options: {
                        animation: false,
                        responsive: false,
                        plugins: {
                            legend: {
                                display: true,
                                labels: {
                                    font: { family: 'Plus Jakarta Sans', size: 9, weight: 'bold' }
                                }
                            }
                        },
                        scales: {
                            y: {
                                grid: { color: '#f1f5f9' },
                                ticks: { font: { family: 'Plus Jakarta Sans', size: 9 } }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { font: { family: 'Plus Jakarta Sans', size: 9, weight: 'bold' } }
                            }
                        }
                    }
                });

                setTimeout(() => {
                    const img = chart.toBase64Image();
                    chart.destroy();
                    resolve(img);
                }, 150);
            });
        }
    </script>
</body>
</html>
