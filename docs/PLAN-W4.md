# Kế hoạch Tuần 4 — Bán hàng (POS), thanh toán, hóa đơn, báo cáo

Mục tiêu: thu ngân **bán hàng tại quầy** trong ca đang mở (trừ kho theo **FEFO**), **thanh toán**, **in hóa đơn**; admin xem **báo cáo/dashboard**. Hoàn tất vòng đời: mở ca → bán → đóng ca (đã có nút đóng ca, tiền dự kiến sẽ tự tính từ thanh toán tiền mặt).

Bảng đã có trong `schema.sql` (không cần bảng mới): `orders`, `order_items`, `payments`, `payment_methods`, `customers`.
Quy ước: `order_items` mỗi dòng gắn **một lô** (`batch_id`); 1 sản phẩm lấy từ nhiều lô → tách nhiều dòng. Tồn bán được = lô còn hạn (`expiry_date IS NULL OR >= CURDATE()`), thứ tự `ORDER BY expiry_date IS NULL, expiry_date, id`.

## Quyết định cần chốt trước khi làm
| # | Vấn đề | Đề xuất |
|---|---|---|
| 1 | Ai bán hàng | `admin`, `cashier`; **bắt buộc có ca đang mở**, hóa đơn gắn `shift_id` |
| 2 | Giỏ hàng nằm ở đâu | Phía trình duyệt (JS), gửi 1 lần khi thanh toán. Server **tính lại toàn bộ** giá/tổng, không tin số từ client |
| 3 | Giá bán | Lấy `sale_price` hiện tại từ DB tại thời điểm thanh toán, lưu vào `unit_price` |
| 4 | Hết hàng giữa chừng | Khóa dòng lô `FOR UPDATE` trong transaction; thiếu tồn thì rollback và báo rõ sản phẩm nào còn bao nhiêu |
| 5 | Thanh toán | Chọn 1 phương thức (tiền mặt / chuyển khoản / ví). Tiền mặt: nhập tiền khách đưa, tính tiền thối. Không chia nhiều phương thức (để sau) |
| 6 | Giảm giá | Giảm theo số tiền (`discount`), 0 ≤ giảm ≤ tạm tính, chỉ `admin` được nhập |
| 7 | Khách hàng / điểm | Tùy chọn tra theo SĐT; tích điểm 1 điểm / 10.000 ₫ (cộng trong cùng transaction). Không dùng điểm để trừ tiền ở Tuần này |
| 8 | Hủy hóa đơn | Chỉ `admin`, chỉ khi ca của hóa đơn còn mở; hoàn tồn lại đúng lô và trừ điểm đã cộng; `status = cancelled` (không xóa) |
| 9 | Mã hóa đơn | `HD-YYYYMMDD-NNNN`, sinh trong transaction, retry khi trùng UNIQUE (giống mã phiếu nhập) |
| 10 | Báo cáo tính cái gì | Chỉ hóa đơn `completed`; doanh thu = `total`; lợi nhuận gộp = Σ(`unit_price` − `cost_price` của lô) × SL |

## Bước 0. Chuẩn bị
- Model: `Order` (tạo/hủy/tìm/lọc), `Customer`; mở rộng `Batch` (`allocateFefo`), `Product` (tìm theo mã vạch/SKU cho POS), `Report`.
- Controller: `PosController`, `OrderController`, `ReportController`; bỏ `pos`, `reports` khỏi `PlaceholderController` (xóa luôn controller nếu hết mục).
- Route (khai báo cụ thể trước `{id}`): `/pos`, `/orders`, `/orders/{id}`, `/orders/{id}/print`, `/orders/{id}/cancel`, `/reports`.
- JS thuần trong `app.js`: giỏ hàng POS (thêm/bớt/xóa, tổng, tiền thối) và chống bấm thanh toán 2 lần (tái dùng `data-once`).

## Bước 1. Lõi bán hàng — dịch vụ FEFO + lưu hóa đơn (chưa có giao diện)
- `Batch::allocateFefo(productId, qty)`: trong transaction, khóa các lô còn hạn theo thứ tự FEFO, trả danh sách `[batch_id, qty]`; thiếu tồn thì ném lỗi nghiệp vụ.
- `Order::checkout(...)` trong **1 transaction**: khóa ca (phải còn `open` và thuộc người bán) → khóa sản phẩm theo `id` tăng dần (tránh deadlock) → kiểm tra SP còn đang bán → phân bổ FEFO → trừ `qty_remaining` → tạo `orders` + `order_items` + `payments` → cộng điểm. Lỗi bất kỳ thì rollback.
- Kiểm thử bằng script/unit trước khi làm giao diện: tách lô đúng thứ tự, không bán lô hết hạn, 2 giao dịch đồng thời không bán lố tồn.

## Bước 2. Giao diện POS (`/pos`)
- Chưa mở ca → thẻ hướng dẫn kèm nút sang `/shifts`, không cho bán.
- Ô tìm sản phẩm (tên/SKU/mã vạch, dùng `/products/search`; Enter thêm ngay khi khớp mã chính xác), hiện giá + tồn bán được; SP hết hàng không thêm được.
- Giỏ hàng: đổi số lượng (không vượt tồn đang biết), xóa dòng, tạm tính; chọn khách theo SĐT (tùy chọn), giảm giá (admin), phương thức thanh toán; tiền mặt nhập tiền khách đưa → hiện tiền thối, chặn nếu thiếu.
- Thanh toán `POST /pos/checkout` (gửi JSON hoặc form mảng) → thành công chuyển sang hóa đơn; lỗi thiếu tồn giữ nguyên giỏ và báo dòng nào cần chỉnh.
- Phím tắt cơ bản (F2 tìm, F9 thanh toán) — làm nếu còn thời gian.

## Bước 3. Hóa đơn: xem, in, danh sách, hủy
- `GET /orders/{id}`: thông tin hóa đơn (mã, thu ngân, khách, giờ), các dòng gộp theo sản phẩm (hiển thị lô nếu cần), tổng, giảm giá, phương thức thanh toán, tiền thối.
- `GET /orders/{id}/print`: bản in khổ nhỏ (CSS `@media print`, ẩn menu), nút "In hóa đơn" gọi `window.print()`.
- `GET /orders`: danh sách + lọc (mã, ngày, thu ngân, phương thức, trạng thái), phân trang; thu ngân chỉ thấy hóa đơn của mình.
- `POST /orders/{id}/cancel` (admin): hoàn tồn đúng lô, trừ điểm, đổi trạng thái; chặn nếu đã hủy hoặc ca đã đóng. Có xác nhận.

## Bước 4. Đóng ca hoàn chỉnh (nối với POS)
- Trang ca hiển thị doanh thu của ca: số hóa đơn, tổng theo từng phương thức, tiền mặt dự kiến (`expectedCash` đã có, tự cộng thanh toán tiền mặt).
- Đóng ca chặn khi đang có thao tác thanh toán dở (khóa dòng ca đã có); lưu chênh lệch để báo cáo. Hóa đơn hủy không tính vào doanh thu ca.
- Trang chi tiết ca `/shifts/{id}` (tùy chọn): danh sách hóa đơn của ca + chênh lệch.

## Bước 5. Báo cáo và dashboard (`/reports`, admin)
- Bộ lọc khoảng ngày (mặc định 7 ngày gần nhất, giới hạn tối đa 366 ngày).
- Thẻ tổng: doanh thu, số hóa đơn, giá trị trung bình/hóa đơn, lợi nhuận gộp.
- Doanh thu theo ngày (bảng + biểu đồ cột đơn giản bằng CSS/SVG, không thêm thư viện), theo phương thức thanh toán, theo thu ngân.
- **Top 10 sản phẩm bán chạy** (theo số lượng và theo doanh thu).
- Dashboard: doanh thu hôm nay / số hóa đơn đã có (`Report::todayRevenue`), kiểm tra lại để chỉ tính `completed`; thêm link sang `/reports`.

## Bước 6. Hoàn thiện
- `seed.sql`: thêm vài hóa đơn mẫu nhiều ngày (một ca đã đóng) để báo cáo có số liệu; chú ý tồn kho các lô khớp sau khi bán.
- `docs/test-cases.md` (POS, hóa đơn, báo cáo), tick README, cập nhật menu (Bán hàng, Hóa đơn, Báo cáo) và phân quyền.
- Rà soát tổng: quyền theo vai trò toàn site, CSRF mọi POST, XSS khi in hóa đơn, README hướng dẫn cài đặt và tài khoản mẫu.

## Test case chính
| # | Kịch bản | Kỳ vọng |
|---|---|---|
| 1 | Bán 1 sản phẩm có 2 lô (HSD khác nhau), SL vượt lô sớm hạn nhất | Tách 2 dòng, trừ lô sớm hạn trước, lô còn lại đúng số |
| 2 | Lô hết hạn còn tồn | Không bị bán; tồn bán được không tính lô đó |
| 3 | Bán vượt tồn / sản phẩm hết hàng | Từ chối, không tạo hóa đơn, không trừ kho, giỏ giữ nguyên |
| 4 | 2 thu ngân bán cùng lúc phần tồn cuối cùng | Chỉ 1 giao dịch thành công, tồn không âm |
| 5 | Client sửa giá/tổng/giảm giá trong request | Server bỏ qua, tính lại từ DB; giảm giá > tạm tính hoặc cashier nhập giảm giá bị từ chối |
| 6 | Chưa mở ca / ca đã đóng / ca của người khác | Không bán được |
| 7 | Tiền mặt: khách đưa thiếu / đủ / dư | Chặn / tiền thối 0 / tiền thối đúng; `payments.amount` = tổng |
| 8 | Lỗi giữa chừng (giả lập dòng thứ 2 lỗi) | Rollback toàn bộ: không hóa đơn, không trừ kho, không cộng điểm |
| 9 | Khách theo SĐT: có / không tồn tại / bỏ trống | Cộng điểm đúng / báo lỗi / bán bình thường |
| 10 | Hủy hóa đơn (admin) | Tồn hoàn đúng lô, điểm bị trừ, doanh thu báo cáo giảm; hủy lần 2 bị chặn; cashier hủy → 403 |
| 11 | Thiếu `_csrf`; `kho1` vào `/pos`; cashier xem hóa đơn người khác | 419; 403; 404/403 |
| 12 | Tên sản phẩm/ghi chú chứa `<script>`, `' OR 1=1 --` trên POS, hóa đơn in, báo cáo | Hiển thị nguyên văn, không lỗi SQL |
| 13 | Đóng ca sau khi bán tiền mặt + chuyển khoản | Tiền dự kiến = đầu ca + tiền mặt; chuyển khoản không tính; chênh lệch báo đúng |
| 14 | Báo cáo: khoảng ngày sai/ngược/quá dài, không có dữ liệu | Tự chỉnh hoặc báo lỗi; trang rỗng không lỗi chia 0 |
| 15 | Top 10 với 12 sản phẩm, đồng hạng, hóa đơn đã hủy | Đúng thứ tự, đủ 10 dòng, không tính hóa đơn hủy |
| 16 | Bấm thanh toán 2 lần / F5 sau khi thanh toán | Chỉ 1 hóa đơn |

## Thứ tự và phụ thuộc
```
Bước 0 ─> Bước 1 Lõi FEFO + checkout ─> Bước 2 POS ─> Bước 3 Hóa đơn ─> Bước 4 Đóng ca ─> Bước 6 Hoàn thiện
                                                  └──────────────> Bước 5 Báo cáo ─────────┘
```
Làm Bước 1 trước giao diện để kiểm thử logic tồn kho/đồng thời độc lập. Bước 5 cần hóa đơn mẫu nên làm sau Bước 3.

## Rủi ro cần để ý
- **Đồng thời/tồn âm**: luôn khóa theo thứ tự id tăng dần, kiểm tra lại `qty_remaining` sau khi khóa.
- **Tin dữ liệu client**: không nhận giá, tổng, `batch_id` từ client; chỉ nhận `product_id`, `qty`.
- **Số tiền**: dùng số nguyên (cột `DECIMAL(12,0)`), tránh float khi tính tiền thối/giảm giá.
- **Phạm vi**: không làm trả hàng một phần, chia nhiều phương thức thanh toán, dùng điểm đổi quà — ghi vào "để sau".

## Tiến độ
- [x] Bước 0
- [x] Bước 1 · [x] Bước 2 · [x] Bước 3 · [x] Bước 4 · [x] Bước 5 · [x] Bước 6
