<?php
/**
 * Cron Job: Aviso de Retraso de Programas Analíticos en Circuito
 * Ejecución recomendada: Diaria (vía CLI o Tarea Programada de Windows)
 */

// Asegurar ejecución desde CLI o Web con paths correctos
require_once __DIR__ . '/../modeloSistema/BDConexionSistema.Class.php';
require_once __DIR__ . '/../modeloSistema/ProgramaPDFDetalle.Class.php';
require_once __DIR__ . '/../modeloSistema/Asignatura.Class.php';
require_once __DIR__ . '/../lib/notificacionesMail/notificacionCircuitoVaspa.php';

echo "=== INICIANDO CRON DE AVISO DE RETRASO DE PROGRAMAS ===" . PHP_EOL;

$conexion = BDConexionSistema::getInstancia();
$anioActual = date("Y");

// Buscar programas en revisión activa, que no estén desaprobados,
// y cuya última fecha de movimiento tenga más de 15 días
$sqlRetrasados = "SELECT ppd.id, ppd.id_asignatura, a.nombre as nombreAsignatura, ppd.fecha_ultimo_movimiento_circuito 
                  FROM programa_pdf_detalle ppd
                  JOIN asignatura a ON ppd.id_asignatura = a.id
                  WHERE ppd.en_revision = 1 
                    AND ppd.fue_desaprobado = 0 
                    AND ppd.anio = {$anioActual}
                    AND ppd.fecha_ultimo_movimiento_circuito <= NOW() - INTERVAL 15 DAY";

$resultado = $conexion->query($sqlRetrasados);

if ($resultado && $resultado->num_rows > 0) {
    $programasRetrasados = array();
    $hoy = new DateTime();
    
    echo "Programas retrasados encontrados: " . $resultado->num_rows . PHP_EOL;
    
    while ($row = $resultado->fetch_assoc()) {
        $idProg = $row['id'];
        $idAsig = $row['id_asignatura'];
        $nombreAsig = $row['nombreAsignatura'];
        
        $fechaMov = new DateTime($row['fecha_ultimo_movimiento_circuito']);
        $diff = $hoy->diff($fechaMov);
        $dias = $diff->days;
        
        // Instanciar modelo para obtener estado actual formateado
        $progModel = new ProgramaPDFDetalle($idProg);
        $estado = $progModel->obtenerEstadoActual();
        
        $programasRetrasados[] = array(
            'idAsignatura' => $idAsig,
            'nombreAsignatura' => $nombreAsig,
            'estado' => $estado,
            'dias' => $dias
        );
        
        echo "- {$idAsig} ({$nombreAsig}) - Estado: {$estado} (Inactivo hace {$dias} días)" . PHP_EOL;
    }
    
    // Mandar el aviso consolidado a VA
    if (notificacionCircuitoVaspa::notificarAvisoAutomatico($programasRetrasados)) {
        echo "Notificación enviada exitosamente a Vinculación Académica." . PHP_EOL;
    } else {
        echo "ERROR: Falló el envío de la notificación a Vinculación Académica." . PHP_EOL;
    }
} else {
    echo "No se encontraron programas retrasados de más de 15 días en revisión." . PHP_EOL;
}

echo "=== CRON FINALIZADO ===" . PHP_EOL;
?>
