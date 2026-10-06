-- ============================================================
-- SKEMA DATABASE RETAIL
-- Jalankan: mysql -u root -p < database/schema.sql
-- ============================================================
CREATE DATABASE IF NOT EXISTS retail CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE retail;

-- Akun pengguna (login/register)
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  business_name VARCHAR(120) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password      VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Produk & stok
CREATE TABLE IF NOT EXISTS products (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sku        VARCHAR(50)  NOT NULL UNIQUE,
  name       VARCHAR(150) NOT NULL,
  category   VARCHAR(80)  NULL,
  unit       VARCHAR(20)  NOT NULL DEFAULT 'pcs',
  buy_price  DECIMAL(15,2) NOT NULL DEFAULT 0,
  sell_price DECIMAL(15,2) NOT NULL DEFAULT 0,
  stock      INT NOT NULL DEFAULT 0,
  min_stock  INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_products_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL, phone VARCHAR(30) NULL, address TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS suppliers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL, phone VARCHAR(30) NULL, address TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Penjualan (header) + item
CREATE TABLE IF NOT EXISTS sales (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_no     VARCHAR(30) NOT NULL UNIQUE,
  customer_id    INT UNSIGNED NULL,
  subtotal       DECIMAL(15,2) NOT NULL DEFAULT 0,
  discount       DECIMAL(15,2) NOT NULL DEFAULT 0,
  total          DECIMAL(15,2) NOT NULL DEFAULT 0,
  paid           DECIMAL(15,2) NOT NULL DEFAULT 0,
  payment_method VARCHAR(20) NOT NULL DEFAULT 'cash',
  note           VARCHAR(255) NULL,
  sale_date      DATETIME NOT NULL,
  INDEX idx_sales_date (sale_date),
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sale_items (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sale_id      INT UNSIGNED NOT NULL,
  product_id   INT UNSIGNED NULL,
  product_name VARCHAR(150) NOT NULL,   -- disalin agar riwayat tetap utuh walau produk dihapus
  qty          INT NOT NULL,
  price        DECIMAL(15,2) NOT NULL,  -- harga jual saat transaksi
  cost         DECIMAL(15,2) NOT NULL,  -- harga modal saat transaksi (untuk hitung laba)
  subtotal     DECIMAL(15,2) NOT NULL,
  FOREIGN KEY (sale_id)    REFERENCES sales(id)    ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Pembelian stok (header) + item
CREATE TABLE IF NOT EXISTS purchases (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ref_no        VARCHAR(30) NOT NULL UNIQUE,
  supplier_id   INT UNSIGNED NULL,
  total         DECIMAL(15,2) NOT NULL DEFAULT 0,
  paid          DECIMAL(15,2) NOT NULL DEFAULT 0,
  note          VARCHAR(255) NULL,
  purchase_date DATETIME NOT NULL,
  INDEX idx_purchases_date (purchase_date),
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchase_items (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  purchase_id  INT UNSIGNED NOT NULL,
  product_id   INT UNSIGNED NULL,
  product_name VARCHAR(150) NOT NULL,
  qty          INT NOT NULL,
  price        DECIMAL(15,2) NOT NULL,
  subtotal     DECIMAL(15,2) NOT NULL,
  FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id)  REFERENCES products(id)  ON DELETE SET NULL
) ENGINE=InnoDB;

-- Piutang (pelanggan berutang ke kita) & Hutang (kita berutang ke supplier)
CREATE TABLE IF NOT EXISTS receivables (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sale_id     INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED NULL,
  amount      DECIMAL(15,2) NOT NULL,
  paid        DECIMAL(15,2) NOT NULL DEFAULT 0,
  status      ENUM('open','paid') NOT NULL DEFAULT 'open',
  due_date    DATE NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sale_id)     REFERENCES sales(id)     ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payables (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  purchase_id INT UNSIGNED NOT NULL,
  supplier_id INT UNSIGNED NULL,
  amount      DECIMAL(15,2) NOT NULL,
  paid        DECIMAL(15,2) NOT NULL DEFAULT 0,
  status      ENUM('open','paid') NOT NULL DEFAULT 'open',
  due_date    DATE NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Buku kas. ref_type/ref_id menandai kas otomatis dari penjualan/pembelian/pelunasan
CREATE TABLE IF NOT EXISTS cash_transactions (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type             ENUM('income','expense') NOT NULL,
  category         VARCHAR(80) NOT NULL,
  description      VARCHAR(255) NULL,
  amount           DECIMAL(15,2) NOT NULL,
  transaction_date DATETIME NOT NULL,
  ref_type         VARCHAR(20) NULL,
  ref_id           INT UNSIGNED NULL,
  INDEX idx_cash_date (transaction_date)
) ENGINE=InnoDB;