<?php
/**
 * Script para registrar la tienda existente en la tabla stores de la plataforma.
 * Ejecutar UNA VEZ: /platform/seed-store.php
 * Después de ejecutar, BORRAR este archivo o protegerlo.
 */
require_once __DIR__ . '/../platform-config.php';
platformStartSession();

if (!isSuperAdmin()) {
    die('Acceso denegado. Necesitas ser superadmin.');
}

$slug = 'wemasha';
$dbName = 'u161673556_tiendamasha';

$existing = platformFetchOne('SELECT id FROM stores WHERE slug = :slug', ['slug' => $slug]);
if ($existing) {
    echo "<p>La tienda <strong>{$slug}</strong> ya existe con ID #{$existing['id']}</p>";
    echo '<p><a href="/platform/superadmin/">Ir al Super Admin</a></p>';
    exit;
}

$userId = $_SESSION['platform_user_id'];

$result = platformQuery(
    "INSERT INTO stores (owner_id, slug, db_name, plan, status) VALUES (:oid, :slug, :db, 'free', 'active')",
    ['oid' => $userId, 'slug' => $slug, 'db' => $dbName]
);

if ($result) {
    $storeId = platformLastInsertId();

    platformQuery(
        "INSERT INTO store_members (store_id, user_id, role) VALUES (:sid, :uid, 'owner')",
        ['sid' => $storeId, 'uid' => $userId]
    );

    echo "<h2>Tienda registrada exitosamente</h2>";
    echo "<p><strong>Slug:</strong> {$slug}</p>";
    echo "<p><strong>DB:</strong> {$dbName}</p>";
    echo "<p><strong>Store ID:</strong> #{$storeId}</p>";
    echo "<br>";
    echo "<p>Ahora podés acceder a:</p>";
    echo "<ul>";
    echo "<li><a href='/{$slug}/'>{$slug}/ - Frontend de la tienda</a></li>";
    echo "<li><a href='/{$slug}/admin/'>{$slug}/admin/ - Panel de admin</a></li>";
    echo "<li><a href='/platform/superadmin/'>Super Admin</a></li>";
    echo "</ul>";
    echo "<br><p><strong>IMPORTANTE:</strong> Borrá este archivo después de usarlo.</p>";
} else {
    echo '<p style="color:red;">Error al registrar la tienda. Revisa los logs.</p>';
}
