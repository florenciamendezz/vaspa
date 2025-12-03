<?php
include_once '../lib/ControlAcceso.Class.php';
include_once '../modeloSistema/Asignatura.Class.php';

$idAsignatura = $_GET["id"];
$Asignatura = new Asignatura($idAsignatura);
$Carreras = $Asignatura->getCarreras();
$nombreCarrera = "";
if ($Carreras) {
    foreach ($Carreras as $Carrera) {
        $nombreCarrera .= $Carrera->getId() . " - " . $Carrera->getNombre() . "<br>";
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../lib/open-iconic-master/font/css/open-iconic-bootstrap.css" />
        <script src="../lib/JQuery/jquery-3.3.1.js"></script>
        <script src="../lib/bootstrap-4.1.1-dist/js/bootstrap.bundle.js"></script>
        <link rel="stylesheet" href="../lib/bootstrap-4.1.1-dist/css/bootstrap.css" />
        <script src="../lib/bootbox/bootbox.js"></script>
        <script src="../lib/bootbox/bootbox.locales.js"></script>
        <title><?php echo Constantes::NOMBRE_SISTEMA; ?> - Cargar Programa PDF</title>
    </head>
    <body>
        <?php include_once '../gui/navbar.php'; ?>

        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h3>Cargar Programa de <?= $Asignatura->getNombre(); ?> (PDF)</h3>
                </div>
                <div class="card-body">
                    <form action="../controlSistema/programa.crear.procesar.pdf.php" method="post" enctype="multipart/form-data" id="form">
                        <input type="hidden" name="idAsignatura" value="<?= $Asignatura->getId(); ?>">
                        
                        <div class="form-group">
                            <label for="anio">Año del Programa</label>
                            <input type="text" class="form-control" id="anio" name="anio" value="<?= date('Y'); ?>" readonly>
                        </div>





                        <div class="form-group">
                            <label for="archivoPDF">Archivo PDF</label>
                            <input type="file" class="form-control-file" id="archivoPDF" name="archivoPDF" accept=".pdf" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Subir Programa</button>
                        <a href="asignaturasDeProfesor.php" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
        <?php include_once '../gui/footer.php'; ?>
    </body>
    <script>
        $("form").submit(function(e){
            e.preventDefault();
            var anio = $('#anio').val();

            var carrera = "<?= $nombreCarrera ?>";
            var asignatura = "<?= $Asignatura->getId() . ' - ' . $Asignatura->getNombre() ?>";
            var nombreArchivo = $('#archivoPDF').prop('files')[0].name;

            bootbox.confirm({
                title: "¿Está seguro de subir el siguiente programa?",
                message: "Datos del programa:<br><i>Año: </i><b>"+anio+"</b><br><i>Carrera: </i><b>"+carrera+"</b><i>Asignatura: </i><b>"+asignatura+"</b><br><i>Archivo: </i><b>"+nombreArchivo+"</b>",
                buttons: {
                    confirm: {
                        label: 'Confirmar',
                        className: 'btn-success'
                    },
                    cancel: {
                        label: 'Cancelar',
                        className: 'btn-danger'
                    }
                },
                callback: function (result) {
                    if (result) {
                        document.getElementById("form").submit();
                    }
                }
            });
        });
    </script>
</html>
