<?php
/**
 * Landing page pública de Somos Tiendi
 */
try {
    require_once __DIR__ . '/../platform-config.php';
    require_once __DIR__ . '/_inc/icon.php';
    platformStartSession();
    $isLogged = platformIsAuthenticated();
} catch (Throwable $e) {
    error_log('[Platform Landing] Error: ' . $e->getMessage());
    $isLogged = false;
}
if (!defined('PLATFORM_PAGES_URL')) {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    define('PLATFORM_PAGES_URL', $proto . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/platform');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Somos Tiendi - Creá tu tienda online en minutos</title>
    <meta name="description" content="Creá tu tienda online gratis y empezá a vender hoy. Sin código, sin complicaciones. La plataforma más simple para emprendedores.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --t-primary: #6366f1;
            --t-primary-hover: #4f46e5;
            --t-primary-light: #eef2ff;
            --t-dark: #0f172a;
            --t-text: #334155;
            --t-muted: #94a3b8;
            --t-border: #e2e8f0;
            --t-bg: #f8fafc;
            --t-card: #ffffff;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { overflow-x: hidden; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; color: var(--t-text); line-height: 1.6; overflow-x: hidden; }

        /* NAV */
        .lp-nav {
            position: fixed; top: 0; width: 100%; z-index: 100;
            background: rgba(255,255,255,0.9); backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--t-border);
            padding: 0 2rem; height: 72px;
            display: flex; align-items: center; justify-content: space-between;
            padding-left: max(2rem, env(safe-area-inset-left));
            padding-right: max(2rem, env(safe-area-inset-right));
        }
        .lp-nav-logo { font-weight: 900; font-size: 1.35rem; color: var(--t-dark); text-decoration: none; }
        .lp-nav-logo span { color: var(--t-primary); }
        .lp-nav-toggle {
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 5px;
            width: 44px;
            height: 44px;
            min-width: 44px;
            min-height: 44px;
            padding: 10px;
            margin: -4px -4px -4px 0;
            background: none;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }
        .lp-nav-toggle span {
            display: block;
            width: 22px;
            height: 2px;
            background: var(--t-dark);
            border-radius: 1px;
            transition: transform 0.3s, opacity 0.3s;
        }
        .lp-nav-toggle.active span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
        .lp-nav-toggle.active span:nth-child(2) { opacity: 0; }
        .lp-nav-toggle.active span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }
        .lp-nav-links { display: flex; align-items: center; gap: 0.75rem; }
        .lp-btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 0.6rem 1.4rem; border-radius: 8px; font-size: 0.9rem;
            font-weight: 600; text-decoration: none; border: none; cursor: pointer;
            font-family: inherit; transition: all 0.2s;
        }
        .lp-btn-ghost { color: var(--t-text); background: transparent; }
        .lp-btn-ghost:hover { background: var(--t-bg); }
        .lp-btn-primary { background: var(--t-primary); color: #fff; }
        .lp-btn-primary:hover { background: var(--t-primary-hover); transform: translateY(-1px); box-shadow: 0 4px 16px rgba(99,102,241,0.35); }
        .lp-btn-lg { padding: 0.85rem 2rem; font-size: 1rem; border-radius: 10px; }
        .lp-btn-white { background: #fff; color: var(--t-primary); font-weight: 700; }
        .lp-btn-white:hover { background: #f1f5f9; transform: translateY(-1px); }

        /* HERO */
        .lp-hero {
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            text-align: center; padding: 6rem 2rem 4rem;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 40%, #312e81 100%);
            color: #fff; position: relative; overflow: hidden;
        }
        .lp-hero::before {
            content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
            background: radial-gradient(circle at 30% 50%, rgba(99,102,241,0.15) 0%, transparent 50%),
                        radial-gradient(circle at 70% 80%, rgba(139,92,246,0.1) 0%, transparent 40%);
            animation: heroGlow 8s ease-in-out infinite alternate;
        }
        @keyframes heroGlow { from { transform: rotate(0deg); } to { transform: rotate(3deg); } }
        .lp-hero-content { position: relative; z-index: 1; max-width: 720px; }
        .lp-hero-badge {
            display: inline-block; padding: 0.4rem 1rem; border-radius: 50px;
            background: rgba(99,102,241,0.2); color: #a5b4fc; font-size: 0.85rem;
            font-weight: 600; margin-bottom: 1.5rem; border: 1px solid rgba(99,102,241,0.3);
        }
        .lp-hero h1 { font-size: 3.5rem; font-weight: 900; line-height: 1.1; margin-bottom: 1.25rem; letter-spacing: -0.03em; }
        .lp-hero h1 span { color: #a5b4fc; }
        .lp-hero p { font-size: 1.2rem; color: #cbd5e1; max-width: 540px; margin: 0 auto 2rem; }
        .lp-hero-cta { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }

        /* STATS BAR */
        .lp-stats {
            background: var(--t-card); border-bottom: 1px solid var(--t-border);
            padding: 2.5rem 2rem;
            display: flex; justify-content: center; gap: 4rem; flex-wrap: wrap;
        }
        .lp-stat { text-align: center; }
        .lp-stat-num { font-size: 2rem; font-weight: 800; color: var(--t-primary); }
        .lp-stat-label { font-size: 0.9rem; color: var(--t-muted); margin-top: 0.25rem; }

        /* FEATURES */
        .lp-section { padding: 5rem 2rem; max-width: 1100px; margin: 0 auto; }
        .lp-section-header { text-align: center; margin-bottom: 3rem; }
        .lp-section-header h2 { font-size: 2.25rem; font-weight: 800; color: var(--t-dark); margin-bottom: 0.75rem; }
        .lp-section-header p { color: var(--t-muted); font-size: 1.05rem; max-width: 550px; margin: 0 auto; }

        .lp-features { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; }
        .lp-feature {
            background: var(--t-card); border: 1px solid var(--t-border); border-radius: 16px;
            padding: 2rem; transition: all 0.3s;
        }
        .lp-feature:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.08); border-color: var(--t-primary); }
        .lp-feature-icon {
            width: 56px; height: 56px; border-radius: 14px; background: var(--t-primary-light);
            display: flex; align-items: center; justify-content: center;
            color: var(--t-primary);
            font-size: 1.75rem; margin-bottom: 1.25rem;
        }
        .lp-feature h3 { font-size: 1.15rem; font-weight: 700; color: var(--t-dark); margin-bottom: 0.5rem; }
        .lp-feature p { color: var(--t-muted); font-size: 0.9rem; }

        /* STEPS */
        .lp-steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; counter-reset: step; }
        .lp-step { text-align: center; position: relative; counter-increment: step; }
        .lp-step::before {
            content: counter(step); display: flex; align-items: center; justify-content: center;
            width: 48px; height: 48px; border-radius: 50%; background: var(--t-primary); color: #fff;
            font-weight: 800; font-size: 1.25rem; margin: 0 auto 1.25rem;
        }
        .lp-step h3 { font-size: 1.1rem; font-weight: 700; color: var(--t-dark); margin-bottom: 0.5rem; }
        .lp-step p { color: var(--t-muted); font-size: 0.9rem; }

        /* CTA FINAL */
        .lp-cta-section {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            padding: 5rem 2rem; text-align: center; color: #fff;
        }
        .lp-cta-section h2 { font-size: 2.25rem; font-weight: 800; margin-bottom: 1rem; }
        .lp-cta-section p { color: #cbd5e1; font-size: 1.1rem; margin-bottom: 2rem; max-width: 500px; margin-left: auto; margin-right: auto; margin-bottom: 2rem; }

        /* FOOTER */
        .lp-footer {
            background: var(--t-dark); color: var(--t-muted); text-align: center;
            padding: 2rem; font-size: 0.85rem;
        }
        .lp-footer a { color: #a5b4fc; text-decoration: none; }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .lp-hero h1 { font-size: 2.25rem; }
            .lp-hero p { font-size: 1rem; }
            .lp-hero { padding-top: 5.5rem; }
            .lp-features, .lp-steps { grid-template-columns: 1fr; }
            .lp-stats { gap: 2rem; padding: 1.5rem 1rem; }
            .lp-section { padding: 3rem 1rem; }
            .lp-section-header h2 { font-size: 1.75rem; }
            .lp-nav { padding: 0 1rem; height: 64px; padding-left: max(1rem, env(safe-area-inset-left)); padding-right: max(1rem, env(safe-area-inset-right)); }
            .lp-nav-toggle { display: flex; }
            .lp-nav-links {
                position: fixed;
                top: 64px;
                left: 0;
                right: 0;
                flex-direction: column;
                align-items: stretch;
                gap: 0.5rem;
                background: rgba(255,255,255,0.98);
                backdrop-filter: blur(12px);
                border-bottom: 1px solid var(--t-border);
                padding: 1rem;
                padding-left: max(1rem, env(safe-area-inset-left));
                padding-right: max(1rem, env(safe-area-inset-right));
                box-shadow: 0 8px 24px rgba(0,0,0,0.12);
                max-height: 0;
                overflow: hidden;
                opacity: 0;
                visibility: hidden;
                transition: max-height 0.35s ease, opacity 0.25s, visibility 0.25s;
                z-index: 99;
            }
            .lp-nav-links.open {
                max-height: 200px;
                opacity: 1;
                visibility: visible;
            }
            .lp-nav-links .lp-btn {
                width: 100%;
                justify-content: center;
                min-height: 44px;
                padding: 0.75rem 1rem;
            }
        }
        @media (max-width: 480px) {
            .lp-hero h1 { font-size: 1.85rem; }
            .lp-hero-cta { flex-direction: column; }
            .lp-hero-cta .lp-btn { width: 100%; }
            .lp-cta-section { padding: 3rem 1rem; }
            .lp-cta-section h2 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
<svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true"><symbol id="icon-package" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16.5 9.4l-9-5.19M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></symbol><symbol id="icon-credit-card" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></symbol><symbol id="icon-smartphone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></symbol><symbol id="icon-bar-chart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></symbol><symbol id="icon-bell" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></symbol><symbol id="icon-image" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></symbol></svg>

<!-- NAV -->
<nav class="lp-nav">
    <a href="<?= PLATFORM_PAGES_URL ?>/" class="lp-nav-logo">Somos <span>Tiendi</span></a>
    <button type="button" class="lp-nav-toggle" id="lp-nav-toggle" aria-label="Abrir menú" aria-expanded="false">
        <span></span><span></span><span></span>
    </button>
    <div class="lp-nav-links" id="lp-nav-links">
        <?php if ($isLogged): ?>
            <a href="<?= PLATFORM_PAGES_URL ?>/dashboard.php" class="lp-btn lp-btn-primary">Mi Panel</a>
        <?php else: ?>
            <a href="<?= PLATFORM_PAGES_URL ?>/login.php" class="lp-btn lp-btn-ghost">Iniciar Sesión</a>
            <a href="<?= PLATFORM_PAGES_URL ?>/register.php" class="lp-btn lp-btn-primary">Registrarse</a>
        <?php endif; ?>
    </div>
</nav>

<!-- HERO -->
<section class="lp-hero">
    <div class="lp-hero-content">
        <div class="lp-hero-badge">La plataforma para emprendedores</div>
        <h1>Creá tu <span>tienda online</span> en minutos</h1>
        <p>Sin código, sin complicaciones. Registrate, armá tu catálogo y empezá a vender hoy mismo.</p>
        <div class="lp-hero-cta">
            <?php if ($isLogged): ?>
                <a href="<?= PLATFORM_PAGES_URL ?>/dashboard.php" class="lp-btn lp-btn-white lp-btn-lg">Ir a Mi Panel</a>
            <?php else: ?>
                <a href="<?= PLATFORM_PAGES_URL ?>/register.php" class="lp-btn lp-btn-white lp-btn-lg">Crear mi tienda gratis</a>
                <a href="#como-funciona" class="lp-btn lp-btn-ghost lp-btn-lg" style="color:#cbd5e1; border:1px solid rgba(255,255,255,0.2);">Cómo funciona</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- STATS -->
<?php
$totalStores = 0;
$totalUsers = 0;
if (function_exists('platformFetchOne')) {
    $storesRow = platformFetchOne('SELECT COUNT(*) as total FROM stores');
    $usersRow = platformFetchOne('SELECT COUNT(*) as total FROM platform_users');
    $totalStores = $storesRow ? ($storesRow['total'] ?? 0) : 0;
    $totalUsers = $usersRow ? ($usersRow['total'] ?? 0) : 0;
}
?>
<div class="lp-stats">
    <div class="lp-stat">
        <div class="lp-stat-num"><?= number_format($totalStores) ?></div>
        <div class="lp-stat-label">Tiendas creadas</div>
    </div>
    <div class="lp-stat">
        <div class="lp-stat-num"><?= number_format($totalUsers) ?></div>
        <div class="lp-stat-label">Emprendedores</div>
    </div>
    <div class="lp-stat">
        <div class="lp-stat-num">100%</div>
        <div class="lp-stat-label">Gratis para empezar</div>
    </div>
</div>

<!-- FEATURES -->
<section class="lp-section">
    <div class="lp-section-header">
        <h2>Todo lo que necesitás para vender</h2>
        <p>Herramientas pensadas para emprendedores que quieren enfocarse en lo importante: su negocio.</p>
    </div>
    <div class="lp-features">
        <div class="lp-feature">
            <div class="lp-feature-icon"><?= icon('package', 28) ?></div>
            <h3>Catálogo de productos</h3>
            <p>Subí tus productos con fotos, precios, categorías y control de stock. Todo desde un panel simple.</p>
        </div>
        <div class="lp-feature">
            <div class="lp-feature-icon"><?= icon('credit-card', 28) ?></div>
            <h3>Cobros integrados</h3>
            <p>Conectá MercadoPago y recibí pagos al instante. Tus clientes pagan seguro y vos cobrás rápido.</p>
        </div>
        <div class="lp-feature">
            <div class="lp-feature-icon"><?= icon('smartphone', 28) ?></div>
            <h3>Tu dominio propio</h3>
            <p>Tu tienda con tu marca: www.somostiendi.com/tu-tienda o conectá tu dominio personalizado.</p>
        </div>
        <div class="lp-feature">
            <div class="lp-feature-icon"><?= icon('bar-chart', 28) ?></div>
            <h3>Panel de administración</h3>
            <p>Gestioná órdenes, cupones de descuento, galería de imágenes y configuraciones desde un solo lugar.</p>
        </div>
        <div class="lp-feature">
            <div class="lp-feature-icon"><?= icon('bell', 28) ?></div>
            <h3>Notificaciones</h3>
            <p>Recibí alertas por Telegram cada vez que alguien hace una compra. Nunca pierdas una venta.</p>
        </div>
        <div class="lp-feature">
            <div class="lp-feature-icon"><?= icon('image', 28) ?></div>
            <h3>Personalizable</h3>
            <p>Elegí colores, subí tu logo, armá tu landing page. Tu tienda con tu identidad.</p>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="lp-section" id="como-funciona" style="background:var(--t-bg); max-width:100%; padding-left:2rem; padding-right:2rem;">
    <div style="max-width:1100px; margin:0 auto;">
        <div class="lp-section-header">
            <h2>Creá tu tienda en 3 pasos</h2>
            <p>Es más fácil de lo que pensás. En menos de 5 minutos tenés todo listo.</p>
        </div>
        <div class="lp-steps">
            <div class="lp-step">
                <h3>Registrate</h3>
                <p>Creá tu cuenta en Somos Tiendi con tu email. Es gratis y toma 30 segundos.</p>
            </div>
            <div class="lp-step">
                <h3>Armá tu tienda</h3>
                <p>Elegí un nombre, subí tus productos y personalizá el diseño a tu gusto.</p>
            </div>
            <div class="lp-step">
                <h3>Empezá a vender</h3>
                <p>Compartí el link de tu tienda y recibí pedidos con pagos integrados.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="lp-cta-section">
    <h2>¿Listo para empezar?</h2>
    <p>Creá tu tienda online gratis hoy mismo y unite a la comunidad de emprendedores.</p>
    <?php if ($isLogged): ?>
        <a href="<?= PLATFORM_PAGES_URL ?>/create-store.php" class="lp-btn lp-btn-white lp-btn-lg">Crear nueva tienda</a>
    <?php else: ?>
        <a href="<?= PLATFORM_PAGES_URL ?>/register.php" class="lp-btn lp-btn-white lp-btn-lg">Crear mi tienda gratis</a>
    <?php endif; ?>
</section>

<!-- FOOTER -->
<footer class="lp-footer">
    <p>&copy; <?= date('Y') ?> <a href="<?= PLATFORM_PAGES_URL ?>/">Somos Tiendi</a>. Todos los derechos reservados.</p>
</footer>

<script>
document.querySelectorAll('a[href^="#"]').forEach(function(a) {
    a.addEventListener('click', function(e) {
        var target = document.querySelector(this.getAttribute('href'));
        if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
    });
});
(function(){
    var t = document.getElementById('lp-nav-toggle');
    var n = document.getElementById('lp-nav-links');
    if (t && n) {
        t.addEventListener('click', function() {
            n.classList.toggle('open');
            t.classList.toggle('active');
            t.setAttribute('aria-expanded', n.classList.contains('open'));
        });
        n.querySelectorAll('a').forEach(function(a) {
            a.addEventListener('click', function() {
                n.classList.remove('open');
                t.classList.remove('active');
                t.setAttribute('aria-expanded', 'false');
            });
        });
    }
})();
</script>
</body>
</html>
