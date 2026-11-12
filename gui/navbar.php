<?php
include_once __DIR__ . '/../lib/ControlAcceso.Class.php'; 

$UsuarioSes = $_SESSION['usuario'] ?? null;
$perfil = "";
if ($UsuarioSes && isset($UsuarioSes->roles[0])) {
    $perfil = $UsuarioSes->roles[0]->nombre;
}
$paginaActual = basename($_SERVER['PHP_SELF'] ?? '');
?>

<meta name="csrf-token" content="<?= ControlAcceso::obtenerCsrfToken(); ?>">

<!-- Lucide Icons local -->
<script src="../lib/lucide.min.js"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap');

    .navbar-premium {
        background: #1d262d !important;
        font-family: 'Roboto', -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
        border-bottom: 4px solid #9ab300 !important;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
        padding: 0 1.75rem;
        min-height: 64px;
        display: flex;
        align-items: center;
    }

    .navbar-premium .navbar-brand {
        font-weight: 700;
        font-size: 1.25rem;
        color: #ffffff !important;
        display: inline-flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.35rem 0;
        margin-right: 2.25rem;
    }

    .navbar-premium .navbar-brand img {
        transition: transform 0.3s ease;
    }

    .navbar-premium .navbar-brand:hover img {
        transform: scale(1.06);
    }

    .navbar-premium .brand-text-wrap {
        display: flex;
        flex-direction: column;
        line-height: 1.15;
    }

    .navbar-premium .brand-main {
        font-size: 1.2rem;
        font-weight: 700;
        color: #ffffff;
        letter-spacing: 0.02em;
    }

    .navbar-premium .brand-sub {
        font-size: 0.72rem;
        font-weight: 400;
        color: #9ba6ae;
        letter-spacing: 0.03em;
    }

    /* ── Nav links ── */
    .navbar-premium .nav-link {
        font-size: 0.95rem;
        font-weight: 500;
        color: #e2e8f0 !important;
        padding: 0 1rem !important;
        border-radius: 0;
        transition: all 0.18s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        height: 60px;
    }

    .navbar-premium .nav-link:hover,
    .navbar-premium .nav-item.active > .nav-link,
    .navbar-premium .nav-item.dropdown:hover > .nav-link {
        color: #ffffff !important;
        background: #9ab300 !important;
    }

    /* Íconos Lucide */
    .navbar-premium .nav-link i[data-lucide],
    .dropdown-content a i[data-lucide] {
        width: 17px;
        height: 17px;
        stroke-width: 2;
        flex-shrink: 0;
    }

    /* ── Dropdown ── */
    .navbar-premium .nav-item.dropdown { position: relative; }

    .dropdown-content {
        display: none;
        position: absolute;
        background: #1d262d;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-top: 3px solid #9ab300;
        border-radius: 0 0 6px 6px;
        min-width: 230px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
        z-index: 1050;
        top: 100%;
        left: 0;
        overflow: hidden;
        padding: 4px 0;
    }

    .dropdown-content::before {
        content: '';
        position: absolute;
        top: -12px; left: 0; right: 0;
        height: 12px;
        background: transparent;
    }

    .dropdown-content a {
        color: #e2e8f0 !important;
        padding: 10px 16px !important;
        text-decoration: none;
        display: flex !important;
        width: 100%;
        align-items: center;
        gap: 0.6rem;
        font-size: 0.92rem;
        font-weight: 500;
        transition: all 0.15s ease;
        background: transparent !important;
    }

    .dropdown-content a:hover {
        background-color: #9ab300 !important;
        color: #ffffff !important;
        padding-left: 20px !important;
    }

    .dropdown-content .dd-divider {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        margin: 4px 0;
    }

    .navbar-premium .nav-item.dropdown:hover .dropdown-content {
        display: block;
    }

    /* ── Badge usuario ── */
    .user-badge {
        background: #ffffff;
        border: 1px solid #d5dce0 !important;
        border-left: 4px solid #9ab300 !important;
        border-radius: 6px;
        color: #334155 !important;
        font-family: 'Roboto', sans-serif;
        font-size: 0.9rem;
        padding: 0.5rem 1.15rem;
        display: flex;
        align-items: center;
        gap: 0.55rem;
        margin: 0.85rem auto;
        max-width: 95%;
        width: fit-content;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .user-badge strong { color: #1d262d; font-weight: 700; }
    .user-badge .close {
        color: #64748b;
        opacity: 0.75;
        font-size: 1rem;
        margin-left: 0.5rem;
        transition: opacity 0.2s;
        padding: 0;
        position: static;
    }
    .user-badge .close:hover { opacity: 1; color: #ef4444; }
</style>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-premium">

    <a class="navbar-brand" href="../vista/inicio.php">
        <img src="../lib/img/VASPA_isotipo.png" width="38" height="30" alt="VASPA">
        <span class="brand-text-wrap">
            <span class="brand-main">VASPA UARG</span>
            <span class="brand-sub">SIT UNPA-UARG</span>
        </span>
    </a>

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarMain"
            aria-controls="navbarMain" aria-expanded="false" aria-label="Menú">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">
        <ul class="navbar-nav mr-auto align-items-center">

            <?php if (ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_VER_VIGENCIA_PROGRAMAS)) { ?>
                <li class="nav-item <?= ($paginaActual === 'panelVA.php') ? 'active' : ''; ?>">
                    <a class="nav-link" href="../vista/panelVA.php">
                        <i data-lucide="bar-chart-2"></i>
                        Vigencias
                    </a>
                </li>
            <?php } ?>

            <?php if (ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_USUARIOS)) { ?>
                <li class="nav-item dropdown <?= (strpos($paginaActual, 'usuario') !== false || strpos($paginaActual, 'rol') !== false || strpos($paginaActual, 'permiso') !== false) ? 'active' : ''; ?>">
                    <a class="nav-link" href="#">
                        <i data-lucide="users"></i>
                        Usuarios
                    </a>
                    <div class="dropdown-content">
                        <a href="../app/usuarios.php">
                            <i data-lucide="user"></i>
                            Usuarios
                        </a>
                        <?php if (ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_ROLES)) { ?>
                            <a href="../app/roles.php">
                                <i data-lucide="shield"></i>
                                Roles
                            </a>
                        <?php } ?>
                        <?php if (ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_PERMISOS)) { ?>
                            <a href="../app/permisos.php">
                                <i data-lucide="lock"></i>
                                Permisos
                            </a>
                        <?php } ?>
                    </div>
                </li>
            <?php } ?>

            <?php if (ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_REVISAR_PROGRAMA) || ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_GENERAR_INFORME_GERENCIAL) || $perfil == PermisosSistema::ROL_VINCULACION_ACADEMICA) { ?>
                <li class="nav-item dropdown <?= (strpos($paginaActual, 'revisar') !== false || strpos($paginaActual, 'monitoreo') !== false || strpos($paginaActual, 'informeGerencial') !== false) ? 'active' : ''; ?>">
                    <a class="nav-link" href="#">
                        <i data-lucide="file-text"></i>
                        Programas
                    </a>
                    <div class="dropdown-content">
                        <?php if (ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_REVISAR_PROGRAMA)) { ?>
                            <a href="../vista/revisar.programas.php">
                                <i data-lucide="clipboard-check"></i>
                                Revisar Programa
                            </a>
                        <?php } ?>
                        <?php if ($perfil == PermisosSistema::ROL_ADMIN || $perfil == PermisosSistema::ROL_VINCULACION_ACADEMICA) { ?>
                            <a href="../vista/monitoreo.circuito.php">
                                <i data-lucide="activity"></i>
                                Monitoreo del Circuito
                            </a>
                        <?php } ?>
                        <?php if (ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_GENERAR_INFORME_GERENCIAL)) { ?>
                            <div class="dd-divider"></div>
                            <a href="../vista/informeGerencial.programas.php">
                                <i data-lucide="trending-up"></i>
                                Informe Gerencial
                            </a>
                        <?php } ?>
                    </div>
                </li>
            <?php } ?>

            <?php if (ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_CARRERAS) || $perfil == PermisosSistema::ROL_VINCULACION_ACADEMICA) { ?>
                <li class="nav-item <?= ($paginaActual === 'carreras.php') ? 'active' : ''; ?>">
                    <a class="nav-link" href="../vista/carreras.php">
                        <i data-lucide="graduation-cap"></i>
                        Carreras
                    </a>
                </li>
                <li class="nav-item <?= ($paginaActual === 'carreras.materias.php') ? 'active' : ''; ?>">
                    <a class="nav-link" href="../vista/carreras.materias.php">
                        <i data-lucide="book-marked"></i>
                        Materias
                    </a>
                </li>
                <li class="nav-item <?= ($paginaActual === 'planes.php') ? 'active' : ''; ?>">
                    <a class="nav-link" href="../vista/planes.php">
                        <i data-lucide="book-open"></i>
                        Planes
                    </a>
                </li>
            <?php } ?>

            <?php if (ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_PROFESORES) || $perfil == PermisosSistema::ROL_VINCULACION_ACADEMICA) { ?>
                <li class="nav-item <?= ($paginaActual === 'profesores.php') ? 'active' : ''; ?>">
                    <a class="nav-link" href="../vista/profesores.php">
                        <i data-lucide="user-check"></i>
                        Profesores
                    </a>
                </li>
            <?php } ?>

            <?php if ($perfil == PermisosSistema::ROL_PROFESOR) { ?>
                <li class="nav-item <?= ($paginaActual === 'asignaturasDeProfesor.php') ? 'active' : ''; ?>">
                    <a class="nav-link" href="../vista/asignaturasDeProfesor.php">
                        <i data-lucide="book-marked"></i>
                        Mis Asignaturas
                    </a>
                </li>
            <?php } ?>

            <?php if (!empty($UsuarioSes)) { ?>
                <li class="nav-item">
                    <a class="nav-link" href="../app/salir.php">
                        <i data-lucide="log-out"></i>
                        Salir
                    </a>
                </li>
            <?php } ?>

        </ul>
    </div>
</nav>

<?php if (!empty($UsuarioSes->nombre)) { ?>
<!-- Badge usuario conectado -->
<div class="user-badge alert alert-dismissible fade show" role="alert">
    <i data-lucide="circle-user" style="width:16px;height:16px;stroke-width:2;color:#0088cc;flex-shrink:0;"></i>
    Conectad@ como <strong><?= e($UsuarioSes->nombre); ?></strong>
    <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php } ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        if (typeof jQuery !== 'undefined') {
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            if (csrfToken) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-Token': csrfToken
                    }
                });
            }
        }
    });
</script>
