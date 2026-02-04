-- Barcraft schema + seed data
-- Adjust database name if needed.

CREATE DATABASE IF NOT EXISTS barcraft
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE barcraft;

CREATE TABLE IF NOT EXISTS drinks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(255) NULL,
  instructions TEXT NOT NULL,
  quote TEXT NULL,
  is_classic TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ingredients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  UNIQUE KEY uniq_ingredient_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS drink_ingredients (
  drink_id INT NOT NULL,
  ingredient_id INT NOT NULL,
  amount VARCHAR(80) NULL,
  PRIMARY KEY (drink_id, ingredient_id),
  CONSTRAINT fk_drink FOREIGN KEY (drink_id) REFERENCES drinks(id) ON DELETE CASCADE,
  CONSTRAINT fk_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  is_approved TINYINT(1) NOT NULL DEFAULT 0,
  language VARCHAR(5) NOT NULL DEFAULT 'en',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_email (email),
  KEY idx_user_approved (is_approved)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_pantry (
  user_id INT NOT NULL,
  ingredient_id INT NOT NULL,
  PRIMARY KEY (user_id, ingredient_id),
  CONSTRAINT fk_pantry_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_pantry_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE
) ENGINE=InnoDB;


