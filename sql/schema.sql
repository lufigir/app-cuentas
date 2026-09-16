CREATE DATABASE IF NOT EXISTS microservicio_cuentas
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE microservicio_cuentas;

CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    document VARCHAR(30) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    account_number VARCHAR(50) UNIQUE NOT NULL,
    created_at DATETIME NOT NULL
);

CREATE DATABASE IF NOT EXISTS gateway_db
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
