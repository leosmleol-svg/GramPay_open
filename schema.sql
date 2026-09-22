CREATE DATABASE IF NOT EXISTS `lk_payments` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `lk_payments`;

-- Таблица пользователей
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(64) NOT NULL UNIQUE,
    `email` VARCHAR(128) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `balance` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица транзакций
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `transaction_id` VARCHAR(128) NULL UNIQUE, -- ID транзакции от Platega
    `payment_method` INT NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,          -- Сумма зачисления на баланс
    `fee` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,-- Надбавка
    `total_amount` DECIMAL(10, 2) NOT NULL,    -- Итого к списанию через шлюз
    `currency` VARCHAR(3) NOT NULL DEFAULT 'RUB',
    `status` VARCHAR(32) NOT NULL DEFAULT 'PENDING',
    `redirect_url` TEXT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_trans_id` (`transaction_id`),
    INDEX `idx_user_status` (`user_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;