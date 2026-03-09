-- Tabla para solicitudes de plan Platinum
CREATE TABLE IF NOT EXISTS platinum_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    contact_name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    shop_name VARCHAR(255) NOT NULL,
    status ENUM('pending', 'contacted', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    admin_notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_store_id (store_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
