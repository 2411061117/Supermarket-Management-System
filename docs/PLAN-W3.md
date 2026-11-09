# Kế hoạch Tuần 3 — Nhập kho, tồn kho, mở ca

Mục tiêu: nhân viên kho lập được **phiếu nhập nhiều dòng (lô, HSD, giá nhập)** → tồn kho tự cộng; xem **tồn kho** và **cảnh báo** (tồn thấp, sắp hết hạn); thu ngân **mở ca** để Tuần 4 bán hàng.

Bảng đã có trong `schema.sql` (không cần bảng mới): `product_batches`, `goods_receipts`, `goods_receipt_items`, `shifts`.
Tồn kho của sản phẩm = `SUM(qty_remaining)` các lô còn hạn (lô hết hạn không tính là có thể bán, giống `/products/search`).

## Quyết định cần chốt trước khi làm
| # | Vấn đề | Đề xuất |
|---|---|---|
| 1 | Phiếu nhập có sửa/xóa không? | **Không.** Phiếu đã nhập là chứng từ; sai thì Tuần sau làm "điều chỉnh/hủy lô". Chỉ có tạo + xem |
| 2 | Trùng mã lô trong cùng sản phẩm | Chặn ở tầng ứng dụng (cùng `product_id` + `batch_code`, kể cả trùng giữa các dòng trong 1 phiếu). Không đổi schema |
| 3 | HSD | Tùy chọn (hàng không có HSD để trống → `NULL`); nếu nhập thì phải ≥ hôm nay |
| 4 | Mã phiếu | Tự sinh `PN-YYYYMMDD-NNN` (đếm trong ngày, trong transaction, có retry khi trùng UNIQUE) |
| 5 | Ai nhập kho / mở ca | Nhập kho: `admin`, `warehouse`. Mở ca: `admin`, `cashier` |
| 6 | Mỗi người tối đa 1 ca đang mở | Có (kiểm tra trong transaction). Schema không ràng buộc nên làm ở ứng dụng |

## Bước 0. Chuẩn bị
- Model: `Batch`, `GoodsReceipt`, `Shift` (+ mở rộng `Report`/`Product` nếu cần truy vấn tồn).
- Đổi `/inventory` từ placeholder thành controller thật; thêm menu con "Tồn kho", "Phiếu nhập".
- `Database::transaction(callable)` đã có sẵn từ Tuần 1 (dùng lại).
- Form nhiều dòng: JS thuần thêm/xóa dòng (tái dùng `partials/field`), có dữ liệu cũ khi validate lỗi (`old()` dạng mảng).

## Bước 1. Tồn kho (`/inventory`) — chỉ đọc, làm trước để thấy kết quả
- Bảng theo **sản phẩm**: SKU, tên, loại, ĐVT, tổng tồn (chỉ lô còn hạn), tồn tối thiểu, trạng thái (Hết hàng / Tồn thấp / Bình thường).
- Lọc: từ khóa, loại hàng, trạng thái tồn; phân trang (tái dùng `Paginator`, `search_bar`).
- Xem **chi tiết lô** của 1 sản phẩm (`/inventory/products/{id}`): mã lô, HSD, SL nhập/còn, giá nhập, ngày nhập; lô hết hạn tô đỏ, sắp hết hạn (≤ 7 ngày) tô vàng; xếp theo FEFO.
- Cảnh báo: tab/bộ lọc "Sắp hết hạn" và "Đã hết hạn còn tồn".
- Dashboard (đã có số liệu low stock / expiring) trỏ link sang trang này.

## Bước 2. Phiếu nhập — xem danh sách và chi tiết
- `GET /receipts`: mã, NCC, người lập, ngày, số dòng, tổng giá trị nhập; lọc theo NCC, khoảng ngày; phân trang.
- `GET /receipts/{id}`: thông tin phiếu + các dòng (sản phẩm, lô, HSD, SL, giá nhập, thành tiền).

## Bước 3. Lập phiếu nhập (`/receipts/create`)
- Chọn NCC (chỉ NCC đang hợp tác), ghi chú; **nhiều dòng**: sản phẩm (chỉ SP đang bán, ô tìm dùng `/products/search`), mã lô, HSD, số lượng, giá nhập.
- Validate từng dòng: sản phẩm tồn tại; mã lô bắt buộc ≤ 50 ký tự; số lượng nguyên 1–1.000.000; giá nhập ≥ 0; HSD hợp lệ và ≥ hôm nay; ít nhất 1 dòng, tối đa ~50 dòng; không trùng lô trong phiếu hoặc với lô đã có.
- Lưu trong **1 transaction**: tạo phiếu → với mỗi dòng tạo `product_batches` (`qty_received = qty_remaining`) → tạo `goods_receipt_items`. Lỗi bất kỳ thì rollback, không sinh dữ liệu dở.
- Chống gửi form 2 lần (nút bị vô hiệu sau khi bấm + kiểm tra trùng mã lô phía server).
- Báo lỗi theo dòng, giữ lại toàn bộ dữ liệu đã nhập.

## Bước 4. Mở ca (`/shifts`)
- `GET /shifts`: ca của tôi (đang mở hoặc chưa); admin xem được mọi ca (lọc theo ngày, nhân viên, trạng thái).
- `POST /shifts/open`: nhập tiền đầu ca (`opening_cash` ≥ 0, số nguyên), ghi chú. Chặn nếu người đó đã có ca mở.
- Hiện trạng thái ca hiện tại trên thanh menu/dashboard ("Ca đang mở từ HH:mm" hoặc nút "Mở ca").
- **Đóng ca để Tuần 4** (cần doanh thu từ POS để đối chiếu tiền mặt); Tuần 3 chỉ mở ca và liệt kê.

## Bước 5. Hoàn thiện
- `seed.sql`: thêm vài lô mẫu (1 lô sắp hết hạn, 1 lô đã hết hạn, 1 sản phẩm tồn thấp, 1 hết hàng) để thấy cảnh báo ngay.
- Test case Tuần 3 vào `docs/test-cases.md`; tick tiến độ README; cập nhật `PlaceholderController` (chỉ còn `pos`, `reports`).

## Test case chính
| # | Kịch bản | Kỳ vọng |
|---|---|---|
| 1 | Lập phiếu 3 dòng hợp lệ | 1 phiếu, 3 lô, tồn từng sản phẩm tăng đúng, mã `PN-…` |
| 2 | Phiếu có 1 dòng lỗi (SL = 0 / giá âm / HSD quá khứ) | Báo lỗi đúng dòng, **không** tạo phiếu hay lô nào, giữ lại dữ liệu |
| 3 | Trùng mã lô (với lô cũ / giữa 2 dòng) | Báo lỗi, không lưu gì |
| 4 | Nhập 2 lô cùng sản phẩm khác HSD | Chi tiết lô xếp FEFO, tổng tồn = tổng 2 lô |
| 5 | Lô hết hạn | Không tính vào tồn bán được; hiện cảnh báo đỏ |
| 6 | Sản phẩm không HSD | Lưu `NULL`, không bao giờ cảnh báo hết hạn |
| 7 | Chọn NCC đã ngừng hợp tác / SP ngừng bán / id không tồn tại | Bị từ chối |
| 8 | Tên lô/ghi chú chứa `<script>`, `' OR 1=1 --` | Hiển thị nguyên văn, không lỗi SQL |
| 9 | Thiếu `_csrf` | 419, không tạo phiếu |
| 10 | Lỗi giữa chừng khi lưu (giả lập lô thứ 2 vi phạm ràng buộc) | Rollback toàn bộ |
| 11 | `thungan1` vào `/receipts*`; `kho1` vào `/shifts/open` | 403 |
| 12 | Mở ca khi đã có ca mở; mở 2 request đồng thời | Chỉ 1 ca được tạo |
| 13 | Tiền đầu ca âm / chữ / rỗng | Báo lỗi |
| 14 | Phân trang + lọc + `' OR 1=1 --` ở ô tìm | Không lỗi, giữ tham số khi sang trang |

## Thứ tự và phụ thuộc
```
Bước 0 ─> Bước 1 Tồn kho (chỉ đọc) ─> Bước 2 Xem phiếu ─> Bước 3 Lập phiếu ─> Bước 5 Hoàn thiện
      └─> Bước 4 Mở ca (độc lập, có thể làm song song) ──────────────────────┘
```
Nên làm Bước 1–2 trước Bước 3: có sẵn trang xem để kiểm tra kết quả khi lập phiếu.

## Tiến độ
- [x] Bước 0
- [x] Bước 1 · [x] Bước 2 · [x] Bước 3 · [x] Bước 4 · [x] Bước 5
