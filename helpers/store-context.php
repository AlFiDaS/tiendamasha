<?php
/**
 * Store Context - Resuelve el slug de tienda y configura la BD correspondiente
 * Usado por store-router.php para multi-tenancy
 */

function resolveStoreBySlug($slug) {
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));
    if (empty($slug)) return null;

    $cacheKey = 'store_ctx_' . $slug;
    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION[$cacheKey])) {
        $cached = $_SESSION[$cacheKey];
        if (time() - ($cached['_ts'] ?? 0) < 300) {
            return $cached;
        }
    }

    require_once dirname(__FILE__) . '/env.php';
    loadEnv();

    try {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            getenv('PLATFORM_DB_HOST') ?: (defined('PLATFORM_DB_HOST') ? PLATFORM_DB_HOST : 'localhost'),
            getenv('PLATFORM_DB_NAME') ?: (defined('PLATFORM_DB_NAME') ? PLATFORM_DB_NAME : ($_ENV['PLATFORM_DB_NAME'] ?? 'somostiendi_platform'))
        );
        $user = getenv('PLATFORM_DB_USER') ?: (defined('PLATFORM_DB_USER') ? PLATFORM_DB_USER : ($_ENV['PLATFORM_DB_USER'] ?? 'root'));
        $pass = getenv('PLATFORM_DB_PASS') ?: (defined('PLATFORM_DB_PASS') ? PLATFORM_DB_PASS : ($_ENV['PLATFORM_DB_PASS'] ?? ''));

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $stmt = $pdo->prepare('SELECT * FROM stores WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $store = $stmt->fetch();

        if (!$store) return null;

        $store['_ts'] = time();
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION[$cacheKey] = $store;
        }

        return $store;
    } catch (PDOException $e) {
        error_log('[StoreContext] DB error resolving slug "' . $slug . '": ' . $e->getMessage());
        return null;
    }
}

/**
 * Configura las constantes de BD y rutas para una tienda específica.
 * Debe llamarse ANTES de incluir config.php.
 */
function setStoreContext($store, $storeSlug) {
    define('STORE_CONTEXT_LOADED', true);
    define('CURRENT_STORE_SLUG', $storeSlug);
    define('CURRENT_STORE_ID', (int) $store['id']);
    define('CURRENT_STORE_DB', $store['db_name']);
    define('CURRENT_STORE_STATUS', $store['status']);
    define('CURRENT_STORE_PLAN', $store['plan']);
    define('CURRENT_STORE_TABLE_PREFIX', $store['table_prefix'] ?? '');
}

/**
 * Inyecta el script de override de fetch/XHR y reescribe links de navegación en el HTML.
 */
function injectStoreContext($html, $slug) {
    $base = '/' . $slug;

    $skip = 'js\\/|_astro\\/|images\\/|css\\/|fonts\\/|favicon|manifest|sw\\.js|offline|global\\.css|platform\\/|api\\/';

    $script = '<script>'
        . 'window.__STORE_BASE="' . $base . '";'
        . '(function(){'
        . 'var b=window.__STORE_BASE;'

        // fetch override
        . 'var f=window.fetch;'
        . 'window.fetch=function(u,o){'
        .   'if(typeof u==="string"){'
        .     'if(u.startsWith("/api/"))u=b+u;'
        .     'else if(u.match(/^https?:\\/\\/[^/]+(:\\d+)?\\/api\\//)){var p=new URL(u);p.pathname=b+p.pathname;u=p.toString()}'
        .   '}'
        .   'return f.call(this,u,o)'
        . '};'

        // XHR override
        . 'var X=XMLHttpRequest.prototype.open;'
        . 'XMLHttpRequest.prototype.open=function(m,u){'
        .   'if(typeof u==="string"&&u.startsWith("/api/"))arguments[1]=b+u;'
        .   'return X.apply(this,arguments)'
        . '};'

        // Helper to fix an anchor's href
        . 'var skip=/^\\/(' . $skip . ')/;'
        . 'function fixA(a){'
        .   'var h=a.getAttribute("href");'
        .   'if(h&&h.charAt(0)==="/"&&h.indexOf(b+"/")!==0&&h!==b&&!skip.test(h)){'
        .     'a.setAttribute("href",b+h)'
        .   '}'
        . '}'

        // MutationObserver: fix dynamically created/modified links
        . 'var mo=new MutationObserver(function(ms){'
        .   'ms.forEach(function(m){'
        .     'if(m.type==="childList"){'
        .       'm.addedNodes.forEach(function(n){'
        .         'if(n.nodeType===1){'
        .           'if(n.tagName==="A")fixA(n);'
        .           'if(n.querySelectorAll){n.querySelectorAll("a[href]").forEach(fixA)}'
        .         '}'
        .       '})'
        .     '}else if(m.type==="attributes"&&m.target.tagName==="A"){'
        .       'fixA(m.target)'
        .     '}'
        .   '})'
        . '});'
        . 'mo.observe(document.documentElement,{childList:true,subtree:true,attributes:true,attributeFilter:["href"]});'

        // window.location override for JS navigations
        . 'var wl=window.location;'
        . 'var origAssign=wl.assign.bind(wl);'
        . 'var origReplace=wl.replace.bind(wl);'
        . 'function fixUrl(u){'
        .   'if(typeof u==="string"&&u.charAt(0)==="/"&&u.indexOf(b+"/")!==0&&u!==b&&!skip.test(u))return b+u;'
        .   'return u'
        . '}'
        . 'wl.assign=function(u){origAssign(fixUrl(u))};'
        . 'wl.replace=function(u){origReplace(fixUrl(u))};'

        . '})();'
        . '</script>';

    $html = str_replace('<head>', '<head>' . "\n" . $script, $html);

    $html = preg_replace_callback(
        '/href="\/([^"]*)"/',
        function ($matches) use ($base) {
            $path = $matches[1];
            if (preg_match('/^(js\/|_astro\/|images\/|css\/|fonts\/|favicon|manifest|sw\.js|offline|global\.css|platform\/)/', $path)) {
                return $matches[0];
            }
            if (strpos($path, 'http') === 0 || strpos($path, '//') === 0) {
                return $matches[0];
            }
            return 'href="' . $base . '/' . $path . '"';
        },
        $html
    );

    return $html;
}
