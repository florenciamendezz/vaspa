<link href="../lib/bootstrap-4.1.1-dist/css/uargflow_footer.css" type="text/css" rel="stylesheet" />
<style>
    .footer-premium {
        background: #1d262d !important;
        font-family: 'Roboto', -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
        border-top: 4px solid #9ab300 !important;
        box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.15);
        color: #e2e8f0 !important;
        height: 58px;
        line-height: 54px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-size: 0.95rem;
        font-weight: 500;
    }

    .footer-premium .oi {
        color: #9ab300;
    }

    .footer-premium strong {
        color: #ffffff;
        font-weight: 700;
    }
</style>
<footer class="footer footer-premium">
    <?php if (!empty($_SESSION['usuario']->nombre)): ?>
        <span class="oi oi-person"></span> 
        Sesi&oacute;n activa: <strong><?= htmlspecialchars($_SESSION['usuario']->nombre, ENT_QUOTES, 'UTF-8'); ?></strong>
        &nbsp;|&nbsp; UNPA-UARG
    <?php else: ?>
        Sistema VASPA &nbsp;|&nbsp; &copy; Servicio de Inform&aacute;tica y Telecomunicaciones (SIT) - UNPA-UARG
    <?php endif; ?>
</footer>
