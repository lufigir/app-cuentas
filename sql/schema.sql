CREATE DATABASE IF NOT EXISTS accounts_db
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE accounts_db;

CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    account_number VARCHAR(20) UNIQUE NOT NULL,
    balance DECIMAL(12,2) NOT NULL DEFAULT 0.00
);

CREATE DATABASE IF NOT EXISTS gateway_db
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
