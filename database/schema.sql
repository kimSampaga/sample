-- Inventory Management System Database
-- This script creates the necessary tables for user authentication and session management

CREATE DATABASE IF NOT EXISTS inventory_system;
USE inventory_system;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    is_active BOOLEAN NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login attempts tracking table
CREATE TABLE IF NOT EXISTS login_attempts (
    username VARCHAR(50) PRIMARY KEY,
    failed_attempts INT DEFAULT 0 NOT NULL,
    last_failed_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (username) REFERENCES users(username) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login logs table
CREATE TABLE IF NOT EXISTS login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    logout_time TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_login_time (login_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample admin user (password: admin123)
INSERT INTO users (username, email, password_hash, first_name, last_name, role, is_active) 
VALUES ('admin', 'admin@inventorysystem.com', '$2y$10$K5xE.0K5K5K5K5K5K5K5K.DzZkDzZkDzZkDzZkDzZ.Z.Z.Z.Z.Z.Z', 'Admin', 'User', 'admin', 1)
ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id);

-- Insert sample regular user (password: user123)
INSERT INTO users (username, email, password_hash, first_name, last_name, role, is_active) 
VALUES ('user', 'user@inventorysystem.com', '$2y$10$L5xE.0L5L5L5L5L5L5L5L.EzZkEzZkEzZkEzZkEzZ.Z.Z.Z.Z.Z.Z', 'John', 'Doe', 'user', 1)
ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id);

-- Products/Inventory Master table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(50) NOT NULL UNIQUE,
    product_name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    brand VARCHAR(50),
    supplier VARCHAR(100),
    unit_of_measure VARCHAR(20),
    cost_price DECIMAL(12, 2) DEFAULT 0.00,
    selling_price DECIMAL(12, 2) DEFAULT 0.00,
    current_stock INT DEFAULT 0,
    reorder_level INT DEFAULT 10,
    expiry_date DATE,
    description TEXT,
    is_active BOOLEAN DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_product_code (product_code),
    INDEX idx_product_name (product_name),
    INDEX idx_category (category),
    INDEX idx_brand (brand),
    INDEX idx_supplier (supplier),
    INDEX idx_is_active (is_active),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock movements/adjustments tracking table
CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    movement_type ENUM('IN', 'OUT', 'ADJUSTMENT', 'RETURN', 'DAMAGE') NOT NULL,
    quantity INT NOT NULL,
    beginning_stock INT NOT NULL,
    ending_stock INT NOT NULL,
    reference VARCHAR(100),
    reason TEXT,
    notes TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_product_id (product_id),
    INDEX idx_movement_type (movement_type),
    INDEX idx_created_at (created_at),
    INDEX idx_product_date (product_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Purchases/Purchase Orders table
CREATE TABLE IF NOT EXISTS purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    supplier_name VARCHAR(100) NOT NULL,
    purchase_date DATE NOT NULL,
    invoice_number VARCHAR(100) NOT NULL UNIQUE,
    quantity INT NOT NULL,
    cost_per_unit DECIMAL(12, 2) NOT NULL,
    total_cost DECIMAL(14, 2) NOT NULL,
    notes TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_product_id (product_id),
    INDEX idx_supplier_name (supplier_name),
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_purchase_date (purchase_date),
    INDEX idx_supplier_date (supplier_name, purchase_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sales/Revenue tracking table
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    sale_date DATE NOT NULL,
    quantity_sold INT NOT NULL,
    unit_price DECIMAL(12, 2) NOT NULL,
    cost_price DECIMAL(12, 2) NOT NULL,
    total_amount DECIMAL(14, 2) NOT NULL,
    payment_type ENUM('CASH', 'CREDIT') NOT NULL DEFAULT 'CASH',
    customer_name VARCHAR(100),
    reference VARCHAR(100),
    notes TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_product_id (product_id),
    INDEX idx_sale_date (sale_date),
    INDEX idx_payment_type (payment_type),
    INDEX idx_customer_name (customer_name),
    INDEX idx_date_payment (sale_date, payment_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Alerts table (stores alert configurations and status)
CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    alert_type ENUM('EXPIRY', 'REORDER') NOT NULL,
    alert_status ENUM('ACTIVE', 'ACKNOWLEDGED', 'RESOLVED') NOT NULL DEFAULT 'ACTIVE',
    is_notified BOOLEAN DEFAULT 0,
    email_sent BOOLEAN DEFAULT 0,
    sms_sent BOOLEAN DEFAULT 0,
    acknowledged_by INT,
    acknowledged_at TIMESTAMP NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (acknowledged_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_product_id (product_id),
    INDEX idx_alert_type (alert_type),
    INDEX idx_alert_status (alert_status),
    INDEX idx_is_notified (is_notified),
    INDEX idx_created_at (created_at),
    INDEX idx_product_type (product_id, alert_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alert logs table (tracks alert notifications sent)
CREATE TABLE IF NOT EXISTS alert_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_id INT NOT NULL,
    notification_type ENUM('EMAIL', 'SMS', 'POPUP') NOT NULL,
    recipient_email VARCHAR(100),
    recipient_phone VARCHAR(20),
    message_content TEXT,
    is_delivered BOOLEAN DEFAULT 0,
    delivery_error TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alert_id) REFERENCES alerts(id) ON DELETE CASCADE,
    INDEX idx_alert_id (alert_id),
    INDEX idx_notification_type (notification_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;