-- =====================================================================
-- CSDL: Quản lý cửa hàng tiện lợi (bản MVP – Plan A)
-- MySQL 8 / MariaDB 10.4+ (XAMPP), chuẩn 3NF, InnoDB, utf8mb4
-- =====================================================================
DROP DATABASE IF EXISTS convenience_store;
CREATE DATABASE convenience_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE convenience_store;

-- ---------- Hệ thống ----------
CREATE TABLE stores (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    address     VARCHAR(255),
    phone       VARCHAR(20),
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE roles (
    id          TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(30) NOT NULL UNIQUE,      -- admin | cashier | warehouse
    name        VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id      INT UNSIGNED NOT NULL,
    role_id       TINYINT UNSIGNED NOT NULL,
    username      VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(100) NOT NULL,
    phone         VARCHAR(20),
    email         VARCHAR(100),
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_store FOREIGN KEY (store_id) REFERENCES stores(id),
    CONSTRAINT fk_users_role  FOREIGN KEY (role_id)  REFERENCES roles(id)
) ENGINE=InnoDB;

-- ---------- Danh mục ----------
CREATE TABLE categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE units (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(50) NOT NULL UNIQUE        -- chai, lon, gói, hộp...
) ENGINE=InnoDB;

CREATE TABLE suppliers (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    contact     VARCHAR(100),
    phone       VARCHAR(20),
    email       VARCHAR(100),
    address     VARCHAR(255),
    is_active   TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE products (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku          VARCHAR(30) NOT NULL UNIQUE,
    barcode      VARCHAR(30) UNIQUE,
    name         VARCHAR(150) NOT NULL,
    category_id  INT UNSIGNED NOT NULL,
    unit_id      INT UNSIGNED NOT NULL,
    sale_price   DECIMAL(12,0) NOT NULL CHECK (sale_price >= 0),
    min_stock    INT UNSIGNED NOT NULL DEFAULT 10,
    image        VARCHAR(255),
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id),
    CONSTRAINT fk_products_unit     FOREIGN KEY (unit_id)     REFERENCES units(id)
) ENGINE=InnoDB;

-- ---------- Kho ----------
-- Mỗi lần nhập tạo 1 lô; tồn kho của sản phẩm = SUM(qty_remaining) các lô
CREATE TABLE product_batches (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id      INT UNSIGNED NOT NULL,
    product_id    INT UNSIGNED NOT NULL,
    batch_code    VARCHAR(50) NOT NULL,
    expiry_date   DATE NULL,
    cost_price    DECIMAL(12,0) NOT NULL CHECK (cost_price >= 0),
    qty_received  INT UNSIGNED NOT NULL,
    qty_remaining INT UNSIGNED NOT NULL,
    received_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_batches_store   FOREIGN KEY (store_id)   REFERENCES stores(id),
    CONSTRAINT fk_batches_product FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_batches_fefo (product_id, expiry_date)
) ENGINE=InnoDB;

CREATE TABLE goods_receipts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code         VARCHAR(30) NOT NULL UNIQUE,
    store_id     INT UNSIGNED NOT NULL,
    supplier_id  INT UNSIGNED NOT NULL,
    user_id      INT UNSIGNED NOT NULL,
    note         VARCHAR(255),
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_receipts_store    FOREIGN KEY (store_id)    REFERENCES stores(id),
    CONSTRAINT fk_receipts_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_receipts_user     FOREIGN KEY (user_id)     REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE goods_receipt_items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    receipt_id  INT UNSIGNED NOT NULL,
    batch_id    INT UNSIGNED NOT NULL UNIQUE,     -- product/HSD/giá nhập lấy từ lô (tránh lặp dữ liệu)
    CONSTRAINT fk_ritems_receipt FOREIGN KEY (receipt_id) REFERENCES goods_receipts(id) ON DELETE CASCADE,
    CONSTRAINT fk_ritems_batch   FOREIGN KEY (batch_id)   REFERENCES product_batches(id)
) ENGINE=InnoDB;

-- ---------- Ca làm việc ----------
CREATE TABLE shifts (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id      INT UNSIGNED NOT NULL,
    user_id       INT UNSIGNED NOT NULL,
    opened_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at     DATETIME NULL,
    opening_cash  DECIMAL(12,0) NOT NULL DEFAULT 0,
    closing_cash  DECIMAL(12,0) NULL,             -- tiền mặt đếm thực tế cuối ca
    status        ENUM('open','closed') NOT NULL DEFAULT 'open',
    note          VARCHAR(255),
    CONSTRAINT fk_shifts_store FOREIGN KEY (store_id) REFERENCES stores(id),
    CONSTRAINT fk_shifts_user  FOREIGN KEY (user_id)  REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------- Khách hàng ----------
CREATE TABLE customers (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone       VARCHAR(20) NOT NULL UNIQUE,
    full_name   VARCHAR(100),
    points      INT UNSIGNED NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Bán hàng ----------
CREATE TABLE payment_methods (
    id          TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(30) NOT NULL UNIQUE,      -- cash | bank | ewallet
    name        VARCHAR(100) NOT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE orders (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code         VARCHAR(30) NOT NULL UNIQUE,
    store_id     INT UNSIGNED NOT NULL,
    channel      ENUM('pos','online') NOT NULL DEFAULT 'pos',
    shift_id     INT UNSIGNED NULL,
    user_id      INT UNSIGNED NULL,               -- nhân viên lập hóa đơn
    customer_id  INT UNSIGNED NULL,
    subtotal     DECIMAL(12,0) NOT NULL,
    discount     DECIMAL(12,0) NOT NULL DEFAULT 0,
    total        DECIMAL(12,0) NOT NULL,
    status       ENUM('completed','cancelled') NOT NULL DEFAULT 'completed',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_store    FOREIGN KEY (store_id)    REFERENCES stores(id),
    CONSTRAINT fk_orders_shift    FOREIGN KEY (shift_id)    REFERENCES shifts(id),
    CONSTRAINT fk_orders_user     FOREIGN KEY (user_id)     REFERENCES users(id),
    CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
    INDEX idx_orders_created (created_at)
) ENGINE=InnoDB;

-- Một sản phẩm có thể bị tách thành nhiều dòng nếu lấy từ nhiều lô (FEFO)
CREATE TABLE order_items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id    INT UNSIGNED NOT NULL,
    batch_id    INT UNSIGNED NOT NULL,            -- suy ra product_id qua lô
    qty         INT UNSIGNED NOT NULL CHECK (qty > 0),
    unit_price  DECIMAL(12,0) NOT NULL,           -- giá bán tại thời điểm bán
    CONSTRAINT fk_oitems_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_oitems_batch FOREIGN KEY (batch_id) REFERENCES product_batches(id)
) ENGINE=InnoDB;

CREATE TABLE payments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id    INT UNSIGNED NOT NULL,
    method_id   TINYINT UNSIGNED NOT NULL,
    amount      DECIMAL(12,0) NOT NULL CHECK (amount > 0),
    paid_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_order  FOREIGN KEY (order_id)  REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_method FOREIGN KEY (method_id) REFERENCES payment_methods(id)
) ENGINE=InnoDB;
