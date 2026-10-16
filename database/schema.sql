CREATE DATABASE IF NOT EXISTS jengaestimate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE jengaestimate;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS materials (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    unit VARCHAR(30) NOT NULL,
    quantity_factor DECIMAL(10,4) NOT NULL DEFAULT 0,
    finish_sensitive TINYINT(1) NOT NULL DEFAULT 0,
    default_price DECIMAL(12,2) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_material_rates (
    user_id BIGINT UNSIGNED NOT NULL,
    material_id INT UNSIGNED NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, material_id),
    CONSTRAINT fk_rates_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_rates_material FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS estimates (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    project_name VARCHAR(100) NOT NULL,
    project_type VARCHAR(30) NOT NULL,
    location VARCHAR(100) NOT NULL,
    floor_area DECIMAL(10,2) NOT NULL,
    floors TINYINT UNSIGNED NOT NULL,
    finish_level VARCHAR(30) NOT NULL,
    transport_percent DECIMAL(5,2) NOT NULL DEFAULT 8.00,
    contingency_percent DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    materials_cost DECIMAL(14,2) NOT NULL,
    labour_cost DECIMAL(14,2) NOT NULL,
    transport_cost DECIMAL(14,2) NOT NULL,
    contingency_cost DECIMAL(14,2) NOT NULL,
    total_cost DECIMAL(14,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_estimates_user_created (user_id, created_at),
    CONSTRAINT fk_estimates_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS estimate_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    estimate_id BIGINT UNSIGNED NOT NULL,
    material_id INT UNSIGNED NULL,
    material_name VARCHAR(100) NOT NULL,
    unit VARCHAR(30) NOT NULL,
    quantity DECIMAL(14,2) NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    total_cost DECIMAL(14,2) NOT NULL,
    CONSTRAINT fk_items_estimate FOREIGN KEY (estimate_id) REFERENCES estimates(id) ON DELETE CASCADE,
    CONSTRAINT fk_items_material FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE SET NULL,
    INDEX idx_items_estimate (estimate_id)
) ENGINE=InnoDB;

INSERT INTO materials (name, unit, quantity_factor, finish_sensitive, default_price, sort_order) VALUES
    ('Cement', '50 kg bag', 0.4200, 0, 850.00, 1),
    ('Building sand', 'm³', 0.0550, 0, 2500.00, 2),
    ('Ballast', 'm³', 0.0600, 0, 2800.00, 3),
    ('Concrete blocks', 'piece', 12.0000, 0, 85.00, 4),
    ('Reinforcement steel', 'kg', 3.5000, 0, 180.00, 5),
    ('Roofing sheets', 'sheet', 0.1200, 0, 1200.00, 6),
    ('Interior paint', 'litre', 0.2500, 1, 450.00, 7)
ON DUPLICATE KEY UPDATE unit = VALUES(unit), quantity_factor = VALUES(quantity_factor),
    finish_sensitive = VALUES(finish_sensitive), default_price = VALUES(default_price),
    sort_order = VALUES(sort_order);
