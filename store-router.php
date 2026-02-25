<?php
/**
 * Store Router - Punto de entrada para todas las requests de tiendas /{slug}/...
 * Recibe _slug y _path desde .htaccess
 */

$storeSlug = $_GET['_slug'] ?? '';
$storePath = $_GET['_path'] ?? '';

$storeSlug = preg_replace('/[^a-z0-9\-]/', '', strtolower($storeSlug));
$storePath = ltrim($storePath, '/');

if (empty($storeSlug)) {
    http_response_code(404);
    echo '404 - Tienda no especificada';
    exit;
}

require_once __DIR__ . '/helpers/store-context.php';

// Sesión aislada por tienda: cookie path = /{slug}/ evita que una tienda pise la sesión de otra
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'path'     => '/' . $storeSlug . '/',
        'httponly' => true,
        'secure'   => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
        'samesite' => 'Lax'
    ]);
    session_name('LUME_ADMIN_SESSION');
    session_start();
}

$store = resolveStoreBySlug($storeSlug);

if (!$store) {
    http_response_code(404);
    readfile(__DIR__ . '/platform/404-store.php');
    exit;
}

if ($store['status'] === 'suspended') {
    http_response_code(403);
    echo '<h1>Tienda suspendida</h1><p>Esta tienda se encuentra temporalmente suspendida.</p>';
    exit;
}

setStoreContext($store, $storeSlug);

unset($_GET['_slug'], $_GET['_path']);

// --- DISPATCH ---

if (preg_match('#^api/(.+)$#', $storePath, $m)) {
    $apiFile = __DIR__ . '/api/' . basename(dirname($m[1])) . '/' . basename($m[1]);
    if (dirname($m[1]) === '.') {
        $apiFile = __DIR__ . '/api/' . basename($m[1]);
    } else {
        $subdir = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', dirname($m[1]));
        $apiFile = __DIR__ . '/api/' . $subdir . '/' . basename($m[1]);
    }

    if (file_exists($apiFile) && pathinfo($apiFile, PATHINFO_EXTENSION) === 'php') {
        $origDir = getcwd();
        chdir(dirname($apiFile));
        require $apiFile;
        chdir($origDir);
        exit;
    }

    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'API endpoint not found']);
    exit;
}

if (preg_match('#^admin/(.*)$#', $storePath, $m)) {
    $adminPath = $m[1] ?: 'index.php';

    if (!preg_match('/\.php/', $adminPath)) {
        if (is_dir(__DIR__ . '/admin/' . $adminPath)) {
            $adminPath = rtrim($adminPath, '/') . '/index.php';
        } else {
            $staticFile = __DIR__ . '/admin/' . $adminPath;
            if (file_exists($staticFile) && is_file($staticFile)) {
                serveStaticFile($staticFile);
                exit;
            }
            $adminPath .= '.php';
        }
    }

    $adminFile = __DIR__ . '/admin/' . $adminPath;
    $realAdmin = realpath($adminFile);
    $realBase = realpath(__DIR__ . '/admin');

    if ($realAdmin && $realBase && strpos($realAdmin, $realBase) === 0 && file_exists($realAdmin)) {
        $origDir = getcwd();
        chdir(dirname($realAdmin));
        require $realAdmin;
        chdir($origDir);
        exit;
    }

    http_response_code(404);
    echo '404 - Página de admin no encontrada';
    exit;
}

if (preg_match('#^images/(.+)$#', $storePath, $m)) {
    $imgPath = $m[1];
    $imgFile = __DIR__ . '/images/' . $imgPath;

    if (!file_exists($imgFile)) {
        $imgFile = __DIR__ . '/public/images/' . $imgPath;
    }

    if (file_exists($imgFile) && is_file($imgFile)) {
        serveStaticFile($imgFile);
        exit;
    }

    http_response_code(404);
    echo '404 - Imagen no encontrada';
    exit;
}

// --- SERVE ASTRO HTML ---

$htmlFile = resolveHtmlFile($storePath);

if ($htmlFile && file_exists($htmlFile)) {
    $html = file_get_contents($htmlFile);
    $html = injectStoreContext($html, $storeSlug);
    $html = injectShopLogo($html, $storeSlug, (int) $store['id']);

    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
}

http_response_code(404);
echo '404 - Página no encontrada';
exit;

// --- HELPER FUNCTIONS ---

function resolveHtmlFile($path) {
    $base = dirname(__FILE__);
    $dist = $base . '/dist';
    $path = trim($path, '/');

    $try = function ($root) use ($path) {
        if (empty($path)) {
            if (file_exists($root . '/index.html')) return $root . '/index.html';
            return null;
        }
        if (file_exists($root . '/' . $path) && is_file($root . '/' . $path)) {
            return $root . '/' . $path;
        }
        if (file_exists($root . '/' . $path . '/index.html')) {
            return $root . '/' . $path . '/index.html';
        }
        if (file_exists($root . '/' . $path . '.html')) {
            return $root . '/' . $path . '.html';
        }
        if (file_exists($root . '/placeholder/placeholder/index.html') && preg_match('#^[^/]+/[^/]+$#', $path)) {
            return $root . '/placeholder/placeholder/index.html';
        }
        if (file_exists($root . '/placeholder/index.html') && preg_match('#^[^/]+$#', $path)) {
            return $root . '/placeholder/index.html';
        }
        if (file_exists($root . '/index.html')) {
            return $root . '/index.html';
        }
        return null;
    };

    $found = $try($base);
    if (!$found && is_dir($dist)) {
        $found = $try($dist);
    }
    return $found;
}

function serveStaticFile($filepath) {
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'svg'  => 'image/svg+xml',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
        'avif' => 'image/avif',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
    ];

    $mime = $mimeTypes[$ext] ?? (@mime_content_type($filepath) ?: 'application/octet-stream');
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: public, max-age=31536000');
    readfile($filepath);
}
