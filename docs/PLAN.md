# Plan bài thực hành: Web quản lý cửa hàng tiện lợi (PHP thuần + MySQL)

## Context
- Môn: Phát triển phần mềm trên nền web. Làm **cá nhân**.
- Đề bài: số hóa vận hành cửa hàng tiện lợi (kho, nhập hàng, POS, bán online, khách hàng thân thiết, khuyến mãi, ca làm việc, báo cáo), phân quyền theo vai trò.
- Thư mục `/home/namtruong/BTHT6` đang trống → làm từ đầu.
- Công nghệ: **PHP 8 thuần (MVC tự viết) + MySQL (XAMPP) + Bootstrap 5 + JavaScript (fetch/AJAX)**, Git/GitHub, Draw.io vẽ biểu đồ.
- Yêu cầu: **2 plan** — (A) bản MVP vài tuần, (B) bản đầy đủ cả học kỳ. Plan B là MVP mở rộng, nên code MVP được tái sử dụng hoàn toàn.

---

## Phần chung (dùng cho cả 2 plan)

### Kiến trúc & cấu trúc thư mục
```
BTHT6/
├── public/                 # DocumentRoot của Apache (chỉ thư mục này public)
│   ├── index.php           # Front controller: mọi request đi qua đây
│   ├── .htaccess           # Rewrite URL -> index.php
│   └── assets/ (css, js, img, uploads)
├── app/
│   ├── Core/               # Router, Database (PDO singleton), Controller, Model, View, Auth, Csrf, Session, Validator
│   ├── Controllers/        # Admin/, Pos/, Shop/ (online), AuthController...
│   ├── Models/             # Product, Batch, Order, Customer, ...
│   ├── Services/           # Logic nghiệp vụ: CheckoutService, InventoryService, PromotionService, PointService
│   ├── Middleware/         # AuthMiddleware, RoleMiddleware
│   └── Views/              # layouts/ (admin, pos, shop), partials/, từng module
├── config/ (config.php, routes.php)
├── database/ (schema.sql, seed.sql, migrations/)
├── docs/ (đặc tả, use case, ERD, BPMN/activity, ảnh chụp màn hình)
└── README.md
```
Nguyên tắc: Controller mỏng → Service xử lý nghiệp vụ + transaction → Model dùng **PDO prepared statements**.

### Quy tắc bảo mật bắt buộc (chấm điểm phi chức năng)
- `password_hash()` / `password_verify()`; `session_regenerate_id()` khi đăng nhập.
- PDO prepared statements (chống SQL Injection); `htmlspecialchars()` khi xuất (chống XSS); CSRF token cho mọi form POST.
- Middleware kiểm tra vai trò cho từng route (RBAC).
- Giao dịch bán hàng/nhập kho dùng **transaction** + `SELECT ... FOR UPDATE` để tồn kho không âm khi nhiều người bán đồng thời.

### Vai trò (roles)
`admin` (Quản lý/Chủ), `cashier` (Thu ngân), `warehouse` (NV kho), `customer` (Khách – bảng riêng).

### Tài liệu phân tích – thiết kế (nộp kèm)
Use case tổng quát + phân rã, đặc tả use case chính, activity diagram cho 5 quy trình (nhập hàng, POS, online, đổi trả, ca), sequence diagram cho thanh toán POS, ERD + lược đồ quan hệ 3NF, wireframe màn hình chính.

---

## PLAN A — Bản MVP (≈ 4 tuần)

**Phạm vi:** đăng nhập/phân quyền, danh mục, sản phẩm, nhập kho theo lô + HSD, POS bán tại quầy, mở/đóng ca, báo cáo cơ bản. *Chưa làm:* bán online, khuyến mãi phức tạp, đổi trả, chấm công.

### CSDL MVP (≈ 14 bảng, 3NF, có PK/FK)
`roles`, `users`, `categories`, `units`, `suppliers`, `products` (sku, barcode, tên, category_id, unit_id, giá bán, min_stock),
`product_batches` (product_id, batch_code, expiry_date, cost_price, qty_remaining),
`goods_receipts`, `goods_receipt_items` (receipt_id, product_id, batch_id, qty, cost_price),
`shifts` (user_id, opened_at, closed_at, opening_cash, closing_cash, status),
`orders` (code, shift_id, user_id, customer_id NULL, channel='pos', subtotal, discount, total, status),
`order_items` (order_id, product_id, batch_id, qty, unit_price, cost_price),
`payment_methods`, `payments` (order_id, method_id, amount),
`customers` (phone, name, points) — chỉ tích điểm đơn giản.

### Lộ trình
| Tuần | Công việc | Sản phẩm bàn giao |
|---|---|---|
| 1 | Phân tích yêu cầu, use case, ERD; cài XAMPP + Git; dựng khung MVC (Router, PDO, View, Auth, CSRF); `schema.sql` + `seed.sql` | Repo GitHub, sơ đồ, đăng nhập/đăng xuất chạy được |
| 2 | CRUD danh mục, đơn vị tính, nhà cung cấp, sản phẩm (upload ảnh, tìm kiếm, phân trang); quản lý tài khoản NV | Trang Admin với Bootstrap responsive |
| 3 | **Nhập kho**: phiếu nhập nhiều dòng (lô, HSD, giá nhập) → tự cộng tồn; danh sách tồn kho, cảnh báo tồn thấp / sắp hết hạn (≤ 7 ngày). **Mở ca** | Module kho hoàn chỉnh |
| 4 | **POS**: tìm/quét barcode (input nhận Enter từ máy quét), giỏ hàng bằng JS, nhập SĐT khách tích điểm, chọn hình thức thanh toán, xuất kho theo **FEFO** (lô hết hạn trước bán trước), in hóa đơn (`window.print()` + CSS print). **Đóng ca** đối chiếu tiền mặt. Dashboard: doanh thu ngày/ca, top 10 bán chạy | Demo hoàn chỉnh + báo cáo |

### Tiêu chí hoàn thành MVP
Thu ngân bán được 1 hóa đơn từ đầu đến cuối, tồn kho giảm đúng lô, doanh thu ghi vào ca, quản lý xem được báo cáo; nhân viên kho không truy cập được trang POS/Admin.

---

## PLAN B — Bản đầy đủ (≈ 12 tuần, cả học kỳ)

Tuần 1–4 giống Plan A (có thể giãn thêm thời gian làm tài liệu). Bổ sung bảng:
`purchase_orders`, `purchase_order_items` (đơn đặt hàng NCC), `supplier_payments` (công nợ NCC),
`stock_movements` (sổ kho mọi biến động: nhập/bán/trả/kiểm kê/hủy hàng hết hạn),
`inventory_checks`, `inventory_check_items`,
`membership_tiers` (Thường/Bạc/Vàng, ngưỡng điểm, % ưu đãi), `point_transactions`,
`promotions` (type: percent / buy_x_get_y / combo, start/end), `promotion_items`, `vouchers`, `order_vouchers`,
`carts`, `cart_items`, `order_status_history`, `shipping_addresses`, `reviews`,
`returns`, `return_items`, `shift_schedules` (phân ca), `attendances`.
(`orders.channel` = `pos` | `online`, thêm `fulfillment` = `pickup` | `delivery`.)

| Tuần | Module | Nội dung chính |
|---|---|---|
| 1–2 | Phân tích & nền tảng | Tài liệu đầy đủ (use case, activity, sequence, ERD); khung MVC, auth, RBAC, layout |
| 3 | Danh mục | Sản phẩm, loại, ĐVT, NCC, tài khoản |
| 4 | Nhập hàng | Đơn đặt hàng NCC (gợi ý từ tồn thấp) → phiếu nhập theo lô/HSD → cập nhật tồn, ghi công nợ NCC |
| 5 | Kho | Kiểm kê (chênh lệch → điều chỉnh), hủy hàng hết hạn, sổ kho `stock_movements`, cảnh báo |
| 6 | POS + ca | Mở/đóng ca, bán hàng FEFO, đa phương thức thanh toán, in hóa đơn, đối chiếu tiền cuối ca |
| 7 | Khuyến mãi & thành viên | `PromotionService` tự áp KM (giảm %, mua 1 tặng 1, combo), mã giảm giá; tích/đổi điểm, tự nâng hạng |
| 8 | Đổi trả | Tra hóa đơn, kiểm tra hạn đổi trả (cấu hình, vd 3 ngày), hoàn tiền/đổi hàng, cộng lại kho, trừ điểm |
| 9 | Shop online (khách) | Đăng ký/đăng nhập khách, duyệt/tìm theo danh mục, giỏ hàng, checkout (nhận tại cửa hàng/giao tận nơi), áp mã giảm giá, thanh toán (COD + mô phỏng chuyển khoản/ví) |
| 10 | Xử lý đơn online | Màn hình NV tiếp nhận đơn, máy trạng thái *Chờ xác nhận → Đang chuẩn bị → Đang giao → Hoàn thành/Hủy* (lưu lịch sử), giữ/trả tồn khi hủy; khách theo dõi đơn + đánh giá |
| 11 | Nhân sự & báo cáo | Phân ca, chấm công cơ bản (check-in/out); báo cáo doanh thu/lợi nhuận theo ngày/tuần/tháng/ca/NV, bán chạy/bán chậm, sắp hết hạn/hết tồn, nhập hàng & công nợ; biểu đồ Chart.js; xuất CSV |
| 12 | Hoàn thiện | Kiểm thử, sửa lỗi, tối ưu index CSDL, responsive trên điện thoại, viết báo cáo, slide, video demo |

### Điểm kỹ thuật cần chú ý (Plan B)
- **Lợi nhuận** = Σ(unit_price − cost_price) × qty từ `order_items` (lưu cost_price lúc bán để báo cáo đúng).
- **Chi nhánh (mở rộng)**: thêm `stores` và `store_id` vào batches/orders/shifts ngay từ đầu (mặc định 1 cửa hàng) để đáp ứng yêu cầu khả năng mở rộng.
- Phương thức thanh toán lưu trong bảng `payment_methods` → thêm phương thức mới không cần sửa code.

---

## Kiểm thử / Verification
1. Import `database/schema.sql` + `seed.sql` vào phpMyAdmin; truy cập `http://localhost/BTHT6/public`.
2. Kịch bản test thủ công (ghi vào `docs/test-cases.md`):
   - Đăng nhập 3 vai trò, thử truy cập URL trái quyền → bị chặn (403).
   - Nhập 2 lô cùng sản phẩm khác HSD → bán → lô HSD gần hơn bị trừ trước.
   - Bán vượt tồn → báo lỗi, transaction rollback.
   - Đóng ca: tiền mặt hệ thống = Σ payments tiền mặt trong ca.
   - (Plan B) Áp KM mua 1 tặng 1 + mã giảm giá; đổi trả → tồn và điểm quay lại; hủy đơn online → trả tồn.
   - Thử SQL injection (`' OR 1=1 --`) ở form đăng nhập/tìm kiếm, XSS (`<script>`) ở tên sản phẩm → không có tác dụng.
3. Kiểm tra responsive bằng DevTools (chế độ điện thoại) cho POS và shop.
4. Commit theo từng tuần trên GitHub để thể hiện tiến độ.

## Bước tiếp theo khi được duyệt
Bắt đầu Tuần 1 (dùng chung cho cả 2 plan): tạo khung thư mục MVC, `app/Core/*`, `public/index.php`, `database/schema.sql` + `seed.sql`, chức năng đăng nhập + phân quyền.
