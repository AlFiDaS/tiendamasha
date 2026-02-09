-- ============================================
-- Tabla: gallery_info
-- ============================================
-- Información editable de la galería: nombre (navbar/breadcrumb),
-- slug (URL) y título (página). Una sola fila.
-- ============================================

CREATE TABLE IF NOT EXISTS `gallery_info` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL DEFAULT 'Galeria de Ideas' COMMENT 'Nombre en navbar y breadcrumb (Inicio / ...)',
  `slug` VARCHAR(100) NOT NULL DEFAULT 'galeria' COMMENT 'Slug para la URL: /slug/',
  `title` VARCHAR(255) NOT NULL DEFAULT 'Galería de ideas' COMMENT 'Título que se muestra en la página de la galería',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Una sola fila con valores por defecto (si ya existe id=1, se actualiza)
INSERT INTO `gallery_info` (`id`, `name`, `slug`, `title`) 
VALUES (1, 'Galeria de Ideas', 'galeria', 'Galería de ideas')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `slug` = VALUES(`slug`), `title` = VALUES(`title`);
