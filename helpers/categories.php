<?php
/**
 * ============================================
 * HELPER: Funciones de Categorías
 * ============================================
 * Funciones auxiliares para trabajar con categorías
 * ============================================
 */

if (!defined('LUME_ADMIN')) {
    die('Acceso directo no permitido');
}

/**
 * Obtener todas las categorías
 * @param bool $onlyVisible Si es true, solo devuelve categorías visibles
 * @return array|false
 */
function getAllCategories($onlyVisible = false) {
    $sql = "SELECT * FROM categories";
    $params = [];
    
    if ($onlyVisible) {
        $sql .= " WHERE visible = 1";
    }
    
    $sql .= " ORDER BY orden ASC, name ASC";
    
    return fetchAll($sql, $params);
}

/**
 * Obtener una categoría por slug
 * @param string $slug
 * @return array|false
 */
function getCategoryBySlug($slug) {
    return fetchOne("SELECT * FROM categories WHERE slug = :slug", ['slug' => $slug]);
}

/**
 * Obtener una categoría por ID
 * @param int $id
 * @return array|false
 */
function getCategoryById($id) {
    return fetchOne("SELECT * FROM categories WHERE id = :id", ['id' => $id]);
}

/**
 * Verificar si un slug de categoría existe
 * @param string $slug
 * @param int|null $excludeId ID a excluir (útil para edición)
 * @return bool
 */
function categorySlugExists($slug, $excludeId = null) {
    $sql = "SELECT COUNT(*) as count FROM categories WHERE slug = :slug";
    $params = ['slug' => $slug];
    
    if ($excludeId !== null) {
        $sql .= " AND id != :excludeId";
        $params['excludeId'] = $excludeId;
    }
    
    $result = fetchOne($sql, $params);
    return ($result && $result['count'] > 0);
}

/**
 * Contar productos en una categoría
 * @param string $categoriaSlug
 * @return int
 */
function countProductsInCategory($categoriaSlug) {
    $result = fetchOne(
        "SELECT COUNT(*) as count FROM products WHERE categoria = :categoria",
        ['categoria' => $categoriaSlug]
    );
    return $result ? (int)$result['count'] : 0;
}

