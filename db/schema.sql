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

-- Seed classic drinks
INSERT INTO drinks (name, description, instructions, quote, is_classic) VALUES
('Negroni', 'Bitter och balanserad klassiker.', 'Ror alla ingredienser med is i ett lagt glas. Garnera med apelsinskal.', 'En elegant bitterhet med rak rygg.', 1);
SET @drink_id = LAST_INSERT_ID();
INSERT INTO ingredients (name) VALUES ('gin') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '3 cl');
INSERT INTO ingredients (name) VALUES ('sot vermouth') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '3 cl');
INSERT INTO ingredients (name) VALUES ('campari') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '3 cl');

INSERT INTO drinks (name, description, instructions, quote, is_classic) VALUES
('Old Fashioned', 'Stark, kryddig och tidlos.', 'Los upp socker med bitters. Tillsatt bourbon och is. Ror och toppa med apelsinskal.', 'En stillsam klassiker med tungt eko.', 1);
SET @drink_id = LAST_INSERT_ID();
INSERT INTO ingredients (name) VALUES ('bourbon') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '5 cl');
INSERT INTO ingredients (name) VALUES ('socker') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '1 tsk');
INSERT INTO ingredients (name) VALUES ('angostura bitters') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '2 stank');
INSERT INTO ingredients (name) VALUES ('apelsinskal') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '1 bit');
INSERT INTO drinks (name, description, instructions, quote, is_classic) VALUES
('Margarita', 'Syrlig, salt och pigg.', 'Skaka tequila, triple sec och limejuice med is. Servera i saltad kant.', 'Sommarens snabba leende.', 1);
SET @drink_id = LAST_INSERT_ID();
INSERT INTO ingredients (name) VALUES ('tequila') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '4 cl');
INSERT INTO ingredients (name) VALUES ('triple sec') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '2 cl');
INSERT INTO ingredients (name) VALUES ('limejuice') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '3 cl');
INSERT INTO ingredients (name) VALUES ('salt') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, 'kant');

INSERT INTO drinks (name, description, instructions, quote, is_classic) VALUES
('Mojito', 'Mynta, citrus och svalka.', 'Muddla mynta och socker med limejuice. Tillsatt rom och is, toppa med soda.', 'Latt, gron och otroligt social.', 1);
SET @drink_id = LAST_INSERT_ID();
INSERT INTO ingredients (name) VALUES ('ljus rom') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '5 cl');
INSERT INTO ingredients (name) VALUES ('limejuice') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '2.5 cl');
INSERT INTO ingredients (name) VALUES ('mynta') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '8 blad');
INSERT INTO ingredients (name) VALUES ('socker') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '2 tsk');
INSERT INTO ingredients (name) VALUES ('sodavatten') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, 'topp');

INSERT INTO drinks (name, description, instructions, quote, is_classic) VALUES
('Martini', 'Torr, ren och ikonisk.', 'Ror gin och torr vermouth med is. Sila och garnera.', 'Minimalist med knivskarp blick.', 1);
SET @drink_id = LAST_INSERT_ID();
INSERT INTO ingredients (name) VALUES ('gin') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '6 cl');
INSERT INTO ingredients (name) VALUES ('torr vermouth') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '1 cl');
INSERT INTO ingredients (name) VALUES ('citronzest') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, 'twist');

INSERT INTO drinks (name, description, instructions, quote, is_classic) VALUES
('Daiquiri', 'Syrligt och stramt.', 'Skaka rom, limejuice och sockerlag med is. Sila i kylt glas.', 'Kort, klar och exakt.', 1);
SET @drink_id = LAST_INSERT_ID();
INSERT INTO ingredients (name) VALUES ('ljus rom') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '5 cl');
INSERT INTO ingredients (name) VALUES ('limejuice') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '2.5 cl');
INSERT INTO ingredients (name) VALUES ('sockerlag') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '1.5 cl');
INSERT INTO drinks (name, description, instructions, quote, is_classic) VALUES
('Whiskey Sour', 'Syrligt, lent och lagom beskt.', 'Skaka whiskey, citronjuice och sockerlag med is. Sila och toppa med is.', 'Mjuk men beslutsam.', 1);
SET @drink_id = LAST_INSERT_ID();
INSERT INTO ingredients (name) VALUES ('whiskey') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '5 cl');
INSERT INTO ingredients (name) VALUES ('citronjuice') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '3 cl');
INSERT INTO ingredients (name) VALUES ('sockerlag') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '2 cl');

INSERT INTO drinks (name, description, instructions, quote, is_classic) VALUES
('Manhattan', 'Djup, varm och kryddig.', 'Ror whiskey, sot vermouth och bitters med is. Sila och garnera.', 'En kostym med sammetskrage.', 1);
SET @drink_id = LAST_INSERT_ID();
INSERT INTO ingredients (name) VALUES ('rye whiskey') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '5 cl');
INSERT INTO ingredients (name) VALUES ('sot vermouth') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '2 cl');
INSERT INTO ingredients (name) VALUES ('angostura bitters') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '2 stank');

INSERT INTO drinks (name, description, instructions, quote, is_classic) VALUES
('Moscow Mule', 'Frisk ingefara med bubblor.', 'Fyll koppar med is. Tillsatt vodka och lime, toppa med ginger beer.', 'En sprakande vinterjacka.', 1);
SET @drink_id = LAST_INSERT_ID();
INSERT INTO ingredients (name) VALUES ('vodka') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '5 cl');
INSERT INTO ingredients (name) VALUES ('limejuice') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '2 cl');
INSERT INTO ingredients (name) VALUES ('ginger beer') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, 'topp');

INSERT INTO drinks (name, description, instructions, quote, is_classic) VALUES
('Espresso Martini', 'Kaffedriv, len och vaken.', 'Skaka vodka, kaffelikor, espresso och sockerlag med is. Sila i kylt glas.', 'Som ett nattligt samtal med koffein.', 1);
SET @drink_id = LAST_INSERT_ID();
INSERT INTO ingredients (name) VALUES ('vodka') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '4 cl');
INSERT INTO ingredients (name) VALUES ('kaffelikor') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '2 cl');
INSERT INTO ingredients (name) VALUES ('espresso') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '3 cl');
INSERT INTO ingredients (name) VALUES ('sockerlag') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);
SET @ing_id = LAST_INSERT_ID();
INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (@drink_id, @ing_id, '1 cl');
