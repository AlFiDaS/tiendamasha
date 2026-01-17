-- ============================================
-- MIGRACIÓN: Configuración de Landing Page
-- ============================================
-- Agrega la tabla landing_page_settings para configurar el contenido del index
-- ============================================

-- Crear tabla landing_page_settings
CREATE TABLE IF NOT EXISTS `landing_page_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `carousel_images` TEXT DEFAULT NULL COMMENT 'JSON: Array de imágenes del carrusel con links',
  `sobre_title` VARCHAR(255) DEFAULT 'Sobre LUME' COMMENT 'Título de la sección Sobre',
  `sobre_text_1` TEXT DEFAULT NULL COMMENT 'Primer párrafo de Sobre',
  `sobre_text_2` TEXT DEFAULT NULL COMMENT 'Segundo párrafo de Sobre',
  `sobre_image` VARCHAR(500) DEFAULT NULL COMMENT 'Ruta de la imagen de Sobre',
  `sobre_stat_1_number` VARCHAR(50) DEFAULT '500+' COMMENT 'Número de primera estadística',
  `sobre_stat_1_label` VARCHAR(100) DEFAULT 'Clientes felices' COMMENT 'Label de primera estadística',
  `sobre_stat_2_number` VARCHAR(50) DEFAULT '50+' COMMENT 'Número de segunda estadística',
  `sobre_stat_2_label` VARCHAR(100) DEFAULT 'Diseños únicos' COMMENT 'Label de segunda estadística',
  `sobre_stat_3_number` VARCHAR(50) DEFAULT '2' COMMENT 'Número de tercera estadística',
  `sobre_stat_3_label` VARCHAR(100) DEFAULT 'Años de experiencia' COMMENT 'Label de tercera estadística',
  `testimonials` TEXT DEFAULT NULL COMMENT 'JSON: Array de comentarios de clientes',
  `testimonials_visible` TINYINT(1) DEFAULT 1 COMMENT 'Mostrar u ocultar comentarios',
  `galeria_title` VARCHAR(255) DEFAULT 'Revisa nuestra galería de ideas' COMMENT 'Título de Galería de Ideas',
  `galeria_description` TEXT DEFAULT NULL COMMENT 'Descripción de Galería de Ideas',
  `galeria_image` VARCHAR(500) DEFAULT NULL COMMENT 'Ruta de la imagen de Galería de Ideas',
  `galeria_link` VARCHAR(255) DEFAULT '/ideas' COMMENT 'Link de Galería de Ideas',
  `galeria_visible` TINYINT(1) DEFAULT 1 COMMENT 'Mostrar u ocultar Galería de Ideas',
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar registro inicial
INSERT INTO `landing_page_settings` (`id`) VALUES (1)
ON DUPLICATE KEY UPDATE `id` = `id`;
