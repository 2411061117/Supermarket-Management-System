-- Dữ liệu mẫu. Mật khẩu của tất cả tài khoản: 123456
USE convenience_store;

INSERT INTO stores (name, address, phone) VALUES
('Cửa hàng tiện lợi 24h - CN1', '123 Nguyễn Trãi, Q.1, TP.HCM', '0281234567');

INSERT INTO roles (code, name) VALUES
('admin',     'Quản lý / Chủ cửa hàng'),
('cashier',   'Nhân viên thu ngân'),
('warehouse', 'Nhân viên kho');

INSERT INTO users (store_id, role_id, username, password_hash, full_name, phone) VALUES
(1, 1, 'admin',    '$2y$10$B4PSCylejTONwoMgwQa.z.PNwRs7ccu9efFpjiUm93PyjtI27rHiu', 'Nguyễn Văn Quản', '0901000001'),
(1, 2, 'thungan1', '$2y$10$B4PSCylejTONwoMgwQa.z.PNwRs7ccu9efFpjiUm93PyjtI27rHiu', 'Trần Thị Thu',    '0901000002'),
(1, 3, 'kho1',     '$2y$10$B4PSCylejTONwoMgwQa.z.PNwRs7ccu9efFpjiUm93PyjtI27rHiu', 'Lê Văn Kho',      '0901000003');

INSERT INTO categories (name, description) VALUES
('Đồ uống', 'Nước ngọt, nước suối, trà, cà phê'),
('Thực phẩm', 'Mì gói, bánh kẹo, đồ ăn nhanh'),
('Sữa', 'Sữa tươi, sữa chua'),
('Hóa mỹ phẩm', 'Dầu gội, kem đánh răng, khăn giấy');

INSERT INTO units (name) VALUES ('Chai'), ('Lon'), ('Gói'), ('Hộp'), ('Cái');

INSERT INTO suppliers (name, contact, phone, email, address) VALUES
('Công ty TNHH Nước giải khát Sài Gòn', 'Anh Minh', '0912000001', 'sales@nuocgk.vn', 'KCN Tân Bình, TP.HCM'),
('Công ty CP Thực phẩm Á Châu',          'Chị Lan',  '0912000002', 'order@acfood.vn', 'Q. Bình Tân, TP.HCM');

INSERT INTO products (sku, barcode, name, category_id, unit_id, sale_price, min_stock) VALUES
('DU001', '8934588012112', 'Nước suối 500ml',        1, 1,  5000, 24),
('DU002', '8935049500018', 'Coca-Cola lon 330ml',    1, 2, 12000, 24),
('TP001', '8934563138165', 'Mì ly hảo hảo',          2, 4, 10000, 20),
('SU001', '8934673573225', 'Sữa tươi có đường 180ml',3, 4,  8000, 30),
('HM001', '8934839120086', 'Khăn giấy bỏ túi',       4, 3,  6000, 10);

INSERT INTO payment_methods (code, name) VALUES
('cash', 'Tiền mặt'), ('bank', 'Chuyển khoản'), ('ewallet', 'Ví điện tử');

INSERT INTO customers (phone, full_name, points) VALUES
('0987654321', 'Phạm Khách Quen', 120);
