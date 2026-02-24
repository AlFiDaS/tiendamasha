<?php
/**
 * Helper para mostrar iconos SVG minimalistas
 * Uso: <?= icon('cart') ?> o <?= icon('check', 24) ?>
 */
function icon($name, $size = 20, $class = '') {
    $cls = trim('icon icon-' . $name . ' ' . $class);
    return '<svg class="' . htmlspecialchars($cls) . '" width="' . (int)$size . '" height="' . (int)$size . '" aria-hidden="true"><use href="#icon-' . htmlspecialchars($name) . '"></use></svg>';
}
