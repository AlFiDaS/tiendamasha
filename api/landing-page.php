<?php
/**
 * API: Configuración de Landing Page
 * Devuelve los datos de configuración de la landing page en JSON
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

require_once '../config.php';

// Asegurar que LUME_ADMIN está definido
if (!defined('LUME_ADMIN')) {
    define('LUME_ADMIN', true);
}

// Asegurar que la columna sobre_visible existe (auto-migración)
$col = fetchOne("SHOW COLUMNS FROM landing_page_settings LIKE 'sobre_visible'");
if (empty($col)) {
    $err = null;
    executeRaw("ALTER TABLE landing_page_settings ADD COLUMN sobre_visible TINYINT(1) DEFAULT 1 COMMENT 'Mostrar u ocultar sección Sobre'", [], $err);
}

// Obtener configuración de landing page
$settings = fetchOne("SELECT * FROM landing_page_settings WHERE id = 1 LIMIT 1");

if (!$settings) {
    // Valores por defecto si no existe configuración
    $settings = [
        'carousel_images' => json_encode([
            ['image' => '/images/hero.webp', 'link' => ''],
            ['image' => '/images/hero2.webp', 'link' => ''],
            ['image' => '/images/hero3.webp', 'link' => '']
        ]),
        'sobre_title' => 'Sobre LUME',
        'sobre_text_1' => 'En LUME creamos velas artesanales inspiradas en emociones y momentos únicos. Nos especializamos en figuras personalizadas para regalar o regalarte algo especial.',
        'sobre_text_2' => 'Usamos materiales de primera calidad y mucho amor en cada detalle, transformando cera en arte que ilumina y aromatiza tus espacios.',
        'sobre_image' => '/images/sobre-nosotros.webp',
        'sobre_stat_1_number' => '500+',
        'sobre_stat_1_label' => 'Clientes felices',
        'sobre_stat_2_number' => '50+',
        'sobre_stat_2_label' => 'Diseños únicos',
        'sobre_stat_3_number' => '2',
        'sobre_stat_3_label' => 'Años de experiencia',
        'sobre_visible' => 1,
        'testimonials' => json_encode([
            ['text' => 'Hermosas velas, ahora mi oficina tiene aroma a café, me encanta.', 'client_name' => 'María G.', 'stars' => '⭐⭐⭐⭐⭐'],
            ['text' => 'Compré una velita de mi perrito que falleció, me largué a llorar, lo hicieron igual.', 'client_name' => 'Carlos L.', 'stars' => '⭐⭐⭐⭐⭐'],
            ['text' => 'Regalé una vela personalizada a mi mamá y se emocionó muchísimo.', 'client_name' => 'Lucía R.', 'stars' => '⭐⭐⭐⭐⭐'],
            ['text' => 'Los souvenirs para el bautismo de mi bebé quedaron hermosos.', 'client_name' => 'Valentina P.', 'stars' => '⭐⭐⭐⭐⭐'],
            ['text' => 'La vela de mi gatito quedó perfecta, hasta los detalles de las manchas.', 'client_name' => 'Diego S.', 'stars' => '⭐⭐⭐⭐⭐']
        ]),
        'testimonials_visible' => 1,
        'galeria_title' => 'Revisa nuestra galería de ideas',
        'galeria_description' => 'Inspírate con más de 30 ideas creativas de velas y decoraciones que puedes pedirnos. Desde velas decorativas, souvenirs y muchas cosas más.',
        'galeria_image' => '/images/0_galeria/idea28.webp',
        'galeria_link' => '/ideas',
        'galeria_visible' => 1
    ];
}

// Decodificar JSONs
$carouselImages = !empty($settings['carousel_images']) ? json_decode($settings['carousel_images'], true) : [];
$testimonials = !empty($settings['testimonials']) ? json_decode($settings['testimonials'], true) : [];

// Preparar respuesta
$response = [
    'carousel' => $carouselImages,
    'productos' => [
        'title' => $settings['productos_title'] ?? 'Más Vendidos',
        'description' => $settings['productos_description'] ?? 'Descubre nuestros productos más populares, elegidos por nuestros clientes',
        'button_text' => $settings['productos_button_text'] ?? 'Ver todos los productos',
        'button_link' => $settings['productos_button_link'] ?? '/productos'
    ],
    'sobre' => [
        'visible' => (bool)($settings['sobre_visible'] ?? 1),
        'title' => $settings['sobre_title'] ?? 'Sobre LUME',
        'text_1' => $settings['sobre_text_1'] ?? '',
        'text_2' => $settings['sobre_text_2'] ?? '',
        'image' => $settings['sobre_image'] ?? '/images/sobre-nosotros.webp',
        'stats' => [
            [
                'number' => $settings['sobre_stat_1_number'] ?? '',
                'label' => $settings['sobre_stat_1_label'] ?? ''
            ],
            [
                'number' => $settings['sobre_stat_2_number'] ?? '',
                'label' => $settings['sobre_stat_2_label'] ?? ''
            ],
            [
                'number' => $settings['sobre_stat_3_number'] ?? '',
                'label' => $settings['sobre_stat_3_label'] ?? ''
            ]
        ]
    ],
    'testimonials' => [
        'visible' => (bool)($settings['testimonials_visible'] ?? 1),
        'items' => $testimonials
    ],
    'galeria' => [
        'visible' => (bool)($settings['galeria_visible'] ?? 1),
        'title' => $settings['galeria_title'] ?? 'Revisa nuestra galería de ideas',
        'description' => $settings['galeria_description'] ?? '',
        'image' => $settings['galeria_image'] ?? '/images/0_galeria/idea28.webp',
        'link' => $settings['galeria_link'] ?? '/ideas',
        'badge' => $settings['galeria_badge'] ?? '✨ Inspiración',
        'features' => !empty($settings['galeria_features']) ? json_decode($settings['galeria_features'], true) : [
            ['icon' => '💡', 'text' => 'Ideas creativas'],
            ['icon' => '🏠', 'text' => 'Decoración hogar'],
            ['icon' => '✨', 'text' => 'Paso a paso']
        ],
        'button_text' => $settings['galeria_button_text'] ?? 'Galeria de ideas'
    ],
    'colors' => [
        'primary_light' => $settings['primary_color_light'] ?? '#ff8c00',
        'primary_dark' => $settings['primary_color_dark'] ?? '#ff8c00'
    ]
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
