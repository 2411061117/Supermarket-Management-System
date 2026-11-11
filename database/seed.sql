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

-- ---------- Tồn kho mẫu (Tuần 3) ----------
-- Ngày tính tương đối so với hôm nay để cảnh báo luôn hiển thị đúng lúc import. Mỗi sản phẩm (id theo thứ tự ở trên):
--   1 DU001 Nước suối  : 100 chai, HSD xa             -> đủ hàng
--   2 DU002 Coca-Cola  : 40 lon sắp hết hạn (5 ngày) + 10 lon ĐÃ hết hạn (không tính vào tồn bán được)
--   3 TP001 Mì ly      : còn 12 (<= tối thiểu 20)      -> tồn thấp
--   4 SU001 Sữa tươi   : chưa nhập                     -> hết hàng
--   5 HM001 Khăn giấy  : 50 gói, không có HSD          -> đủ hàng
INSERT INTO goods_receipts (id, code, store_id, supplier_id, user_id, note, created_at) VALUES
(1, CONCAT('PN-', DATE_FORMAT(CURDATE() - INTERVAL 10 DAY, '%Y%m%d'), '-001'), 1, 1, 3, 'Nhập đồ uống đầu kỳ',  CURDATE() - INTERVAL 10 DAY + INTERVAL 9 HOUR),
(2, CONCAT('PN-', DATE_FORMAT(CURDATE() - INTERVAL 3 DAY,  '%Y%m%d'), '-001'), 1, 2, 3, 'Nhập thực phẩm',      CURDATE() - INTERVAL 3 DAY  + INTERVAL 14 HOUR);

INSERT INTO product_batches (id, store_id, product_id, batch_code, expiry_date, cost_price, qty_received, qty_remaining, received_at) VALUES
(1, 1, 1, 'NS-2501', CURDATE() + INTERVAL 180 DAY, 3000, 100, 100, CURDATE() - INTERVAL 10 DAY + INTERVAL 9 HOUR),
(2, 1, 2, 'CC-2501', CURDATE() + INTERVAL 5 DAY,   9000,  60,  40, CURDATE() - INTERVAL 10 DAY + INTERVAL 9 HOUR),
(3, 1, 2, 'CC-2412', CURDATE() - INTERVAL 3 DAY,   9000,  30,  10, CURDATE() - INTERVAL 10 DAY + INTERVAL 9 HOUR),
(4, 1, 3, 'MI-2501', CURDATE() + INTERVAL 90 DAY,  7000,  30,  12, CURDATE() - INTERVAL 3 DAY + INTERVAL 14 HOUR),
(5, 1, 5, 'KG-01',   NULL,                         3500,  50,  50, CURDATE() - INTERVAL 3 DAY + INTERVAL 14 HOUR);

INSERT INTO goods_receipt_items (receipt_id, batch_id) VALUES (1, 1), (1, 2), (1, 3), (2, 4), (2, 5);

INSERT INTO payment_methods (code, name) VALUES
('cash', 'Tiền mặt'), ('bank', 'Chuyển khoản'), ('ewallet', 'Ví điện tử');

INSERT INTO customers (phone, full_name, points) VALUES
('0987654321', 'Phạm Khách Quen', 120);

-- ---------- Hóa đơn mẫu (Tuần 4) ----------
-- 3 ca đã đóng của thungan1 (user 2) ở các ngày khác nhau để trang /reports có số liệu. Không có ca đang mở,
-- nên đăng nhập thu ngân vẫn phải mở ca trước khi bán. Số lượng bán KHỚP với tồn ở product_batches phía trên
-- (qty_received − qty_remaining): CC-2412 bán 20, CC-2501 bán 20, MI-2501 bán 18. Hóa đơn hủy đã hoàn kho nên không tính.
-- expected_cash = tiền đầu ca + tiền mặt thu được; chênh lệch = closing_cash − expected_cash.
INSERT INTO shifts (id, store_id, user_id, opened_at, closed_at, opening_cash, closing_cash, expected_cash, status, note) VALUES
(1, 1, 2, CURDATE() - INTERVAL 8 DAY + INTERVAL 8 HOUR, CURDATE() - INTERVAL 8 DAY + INTERVAL 16 HOUR, 500000, 644000, 644000, 'closed', 'Ca sáng - khớp tiền'),
(2, 1, 2, CURDATE() - INTERVAL 5 DAY + INTERVAL 8 HOUR, CURDATE() - INTERVAL 5 DAY + INTERVAL 16 HOUR, 300000, 419000, 420000, 'closed', 'Ca sáng - thiếu 1.000 ₫'),
(3, 1, 2, CURDATE() - INTERVAL 2 DAY + INTERVAL 8 HOUR, CURDATE() - INTERVAL 2 DAY + INTERVAL 16 HOUR, 200000, 380000, 380000, 'closed', 'Ca có 1 hóa đơn hủy');

INSERT INTO orders (id, code, store_id, channel, shift_id, user_id, customer_id, subtotal, discount, total, status, created_at) VALUES
(1, CONCAT('HD-', DATE_FORMAT(CURDATE() - INTERVAL 8 DAY, '%Y%m%d'), '-0001'), 1, 'pos', 1, 2, NULL, 144000,    0, 144000, 'completed', CURDATE() - INTERVAL 8 DAY + INTERVAL 9 HOUR),
(2, CONCAT('HD-', DATE_FORMAT(CURDATE() - INTERVAL 8 DAY, '%Y%m%d'), '-0002'), 1, 'pos', 1, 2, NULL,  96000, 6000,  90000, 'completed', CURDATE() - INTERVAL 8 DAY + INTERVAL 11 HOUR),
(3, CONCAT('HD-', DATE_FORMAT(CURDATE() - INTERVAL 5 DAY, '%Y%m%d'), '-0001'), 1, 'pos', 2, 2, NULL, 120000,    0, 120000, 'completed', CURDATE() - INTERVAL 5 DAY + INTERVAL 10 HOUR),
(4, CONCAT('HD-', DATE_FORMAT(CURDATE() - INTERVAL 5 DAY, '%Y%m%d'), '-0002'), 1, 'pos', 2, 2, NULL, 120000,    0, 120000, 'completed', CURDATE() - INTERVAL 5 DAY + INTERVAL 14 HOUR),
(5, CONCAT('HD-', DATE_FORMAT(CURDATE() - INTERVAL 2 DAY, '%Y%m%d'), '-0001'), 1, 'pos', 3, 2, NULL, 100000,    0, 100000, 'completed', CURDATE() - INTERVAL 2 DAY + INTERVAL 9 HOUR),
(6, CONCAT('HD-', DATE_FORMAT(CURDATE() - INTERVAL 2 DAY, '%Y%m%d'), '-0002'), 1, 'pos', 3, 2, NULL,  80000,    0,  80000, 'completed', CURDATE() - INTERVAL 2 DAY + INTERVAL 13 HOUR),
(7, CONCAT('HD-', DATE_FORMAT(CURDATE() - INTERVAL 2 DAY, '%Y%m%d'), '-0003'), 1, 'pos', 3, 2, NULL,  20000,    0,  20000, 'cancelled', CURDATE() - INTERVAL 2 DAY + INTERVAL 15 HOUR);

-- batch_id: 2 = CC-2501, 3 = CC-2412, 4 = MI-2501
INSERT INTO order_items (order_id, batch_id, qty, unit_price) VALUES
(1, 3, 12, 12000), (2, 3, 8, 12000),
(3, 2, 10, 12000), (4, 2, 10, 12000),
(5, 4, 10, 10000), (6, 4,  8, 10000), (7, 4, 2, 10000);

-- method_id: 1 = Tiền mặt, 2 = Chuyển khoản
INSERT INTO payments (order_id, method_id, amount, paid_at) VALUES
(1, 1, 144000, CURDATE() - INTERVAL 8 DAY + INTERVAL 9 HOUR),
(2, 2,  90000, CURDATE() - INTERVAL 8 DAY + INTERVAL 11 HOUR),
(3, 1, 120000, CURDATE() - INTERVAL 5 DAY + INTERVAL 10 HOUR),
(4, 2, 120000, CURDATE() - INTERVAL 5 DAY + INTERVAL 14 HOUR),
(5, 1, 100000, CURDATE() - INTERVAL 2 DAY + INTERVAL 9 HOUR),
(6, 1,  80000, CURDATE() - INTERVAL 2 DAY + INTERVAL 13 HOUR),
(7, 1,  20000, CURDATE() - INTERVAL 2 DAY + INTERVAL 15 HOUR);
