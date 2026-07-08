<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xampp/php/logs/php_error_log');
include_once '../lib/ControlAcceso.Class.php'; 

$UsuarioSes = $_SESSION['usuario'];
$perfil = "";
if (isset($UsuarioSes->roles[0])) {
    $perfil = $UsuarioSes->roles[0]->nombre;
}
?>

<!-- Lucide Icons (moderno, SVG limpio) -->
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    .navbar-premium {
        background: #0f172a !important;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        box-shadow: 0 1px 0 rgba(255,255,255,0.06), 0 4px 20px rgba(0,0,0,0.2);
        padding: 0 1.5rem;
        height: 56px;
        display: flex;
        align-items: center;
    }

    .navbar-premium .navbar-brand {
        font-weight: 700;
        font-size: 1.1rem;
        letter-spacing: 0.05em;
        color: #f8fafc !important;
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        text-transform: uppercase;
        padding: 0;
        margin-right: 2rem;
    }
    .navbar-premium .navbar-brand img {
        transition: transform 0.3s ease;
        opacity: 0.95;
    }
    .navbar-premium .navbar-brand:hover img { transform: scale(1.06); }

    /* ── Nav links ── */
    .navbar-premium .nav-link {
        font-size: 0.8rem;
        font-weight: 500;
        letter-spacing: 0.02em;
        color: #94a3b8 !important;
        padding: 0 0.75rem !important;
        border-radius: 0;
        transition: all 0.18s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        height: 56px;
        border-bottom: 2px solid transparent;
    }
    .navbar-premium .nav-link:hover {
        color: #f1f5f9 !important;
        background: rgba(255,255,255,0.04);
        border-bottom-color: rgba(99,102,241,0.4);
    }
    .navbar-premium .nav-item.dropdown:hover > .nav-link {
        color: #f8fafc !important;
        border-bottom-color: #6366f1;
        background: rgba(99,102,241,0.06);
    }

    /* Íconos Lucide */
    .navbar-premium .nav-link i[data-lucide],
    .dropdown-content a i[data-lucide] {
        width: 15px;
        height: 15px;
        stroke-width: 2;
        flex-shrink: 0;
    }

    /* ── Dropdown ── */
    .navbar-premium .nav-item.dropdown { position: relative; }

    .dropdown-content {
        display: none;
        position: absolute;
        background: #111827;
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 10px;
        min-width: 220px;
        box-shadow: 0 16px 40px rgba(0,0,0,0.35);
        z-index: 1050;
        top: calc(100% + 4px);
        left: 0;
        overflow: hidden;
        padding: 4px 0;
    }
    /* Puente invisible anti-gap */
    .dropdown-content::before {
        content: '';
        position: absolute;
        top: -12px; left: 0; right: 0;
        height: 12px;
        background: transparent;
    }
    .dropdown-content a {
        color: #94a3b8 !important;
        padding: 9px 14px !important;
        text-decoration: none;
        display: flex !important;
        width: 100%;
        align-items: center;
        gap: 0.55rem;
        font-size: 0.8rem;
        font-weight: 500;
        letter-spacing: 0.01em;
        transition: all 0.15s ease;
        background: transparent !important;
    }
    .dropdown-content a:hover {
        background-color: rgba(99,102,241,0.08) !important;
        color: #e2e8f0 !important;
        padding-left: 18px !important;
    }
    .dropdown-content .dd-divider {
        border-top: 1px solid rgba(255,255,255,0.06);
        margin: 3px 0;
    }
    .navbar-premium .nav-item.dropdown:hover .dropdown-content {
        display: block;
        animation: dropFade 0.15s ease-out;
    }
    @keyframes dropFade {
        from { opacity: 0; transform: translateY(4px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Badge usuario ── */
    .user-badge {
        background: #f8fafc;
        border: 1px solid #e2e8f0 !important;
        border-radius: 8px;
        color: #475569 !important;
        font-family: 'Inter', sans-serif;
        font-size: 0.78rem;
        padding: 0.4rem 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0.75rem auto;
        max-width: 95%;
        width: fit-content;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .user-badge strong { color: #0f172a; font-weight: 600; }
    .user-badge .close {
        color: #94a3b8;
        opacity: 0.7;
        font-size: 0.9rem;
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
        <img src="../lib/img/VASPA_isotipo.png" width="36" height="28" alt="VASPA">
        VASPA
    </a>

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarMain"
            aria-controls="navbarMain" aria-expanded="false" aria-label="Menú">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">
        <ul class="navbar-nav mr-auto align-items-center">

            <?php if (ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_VER_VIGENCIA_PROGRAMAS)) { ?>
                <li class="nav-item">
                    <a class="nav-link" href="../vista/panelVA.php">
                        <i data-lucide="bar-chart-2"></i>
                        Vigencias
                    </a>
                </li>
            <?php } ?>

            <?php if (ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_USUARIOS)) { ?>
                <li class="nav-item dropdown">
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
                <li class="nav-item dropdown">
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
                <li class="nav-item">
                    <a class="nav-link" href="../vista/carreras.php">
                        <i data-lucide="graduation-cap"></i>
                        Carreras
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../vista/carreras.materias.php">
                        <i data-lucide="book-marked"></i>
                        Materias
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../vista/planes.php">
                        <i data-lucide="book-open"></i>
                        Planes
                    </a>
                </li>
            <?php } ?>

            <?php if (ControlAcceso::verificaPermiso(PermisosSistema::PERMISO_PROFESORES) || $perfil == PermisosSistema::ROL_VINCULACION_ACADEMICA) { ?>
                <li class="nav-item">
                    <a class="nav-link" href="../vista/profesores.php">
                        <i data-lucide="user-check"></i>
                        Profesores
                    </a>
                </li>
            <?php } ?>

            <?php if ($perfil == PermisosSistema::ROL_PROFESOR) { ?>
                <li class="nav-item">
                    <a class="nav-link" href="../vista/asignaturasDeProfesor.php">
                        <i data-lucide="book-marked"></i>
                        Mis Asignaturas
                    </a>
                </li>
            <?php } ?>

            <li class="nav-item">
                <a class="nav-link" href="../app/salir.php">
                    <i data-lucide="log-out"></i>
                    Salir
                </a>
            </li>

        </ul>
    </div>
</nav>

<!-- Badge usuario conectado -->
<div class="user-badge alert alert-dismissible fade show" role="alert">
    <i data-lucide="circle-user" style="width:14px;height:14px;stroke-width:2;color:#6366f1;flex-shrink:0;"></i>
    Conectad@ como <strong><?= $_SESSION['usuario']->nombre; ?></strong>
    <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
        <span aria-hidden="true">&times;</span>
    </button>
</div>

<script>
    if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
