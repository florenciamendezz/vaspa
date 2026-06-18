<link href="../lib/bootstrap-4.1.1-dist/css/uargflow_footer.css" type="text/css" rel="stylesheet" />
<style>
    /* Estilos premium para el footer */
    .footer-premium {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
        font-family: 'Outfit', sans-serif;
        border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
        color: #94a3b8 !important;
        height: 55px;
        line-height: 53px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .footer-premium .oi {
        color: #38bdf8;
        filter: drop-shadow(0 0 4px rgba(56, 189, 248, 0.4));
    }

    .footer-premium strong {
        color: #f8fafc;
        font-weight: 600;
    }
</style>
<footer class="footer footer-premium">
    <span class="oi oi-person"></span> 
    Sesión activa: <strong><?php $usuario = $_SESSION['usuario']; echo $usuario->nombre; ?></strong>
</footer>

