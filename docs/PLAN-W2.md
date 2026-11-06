# Plan Tuần 2: Danh mục, đơn vị tính, nhà cung cấp, sản phẩm, tài khoản

Thứ tự đi từ cái đơn giản, độc lập đến cái phụ thuộc nhiều. Làm xong bước nào là chạy được và test được bước đó.

## Lưu ý từ code hiện tại
- `Router` đã hỗ trợ `{id}` và tự kiểm tra CSRF cho mọi POST. Tham số URL luôn là chuỗi → controller phải ép kiểu `(int)`.
- `Model::delete()` xóa thẳng. `categories`, `units`, `suppliers` bị FK trỏ tới nên xóa sẽ ném `PDOException 23000` → phải bắt lỗi và báo "đang được sử dụng". Sản phẩm và nhân viên thì **khóa (`is_active=0`)** thay vì xóa.
- Menu ở `layouts/main.php` ban đầu chỉ có 1 mục `/catalog` → tách thành nhiều mục.
- Ban đầu chưa có Validator, phân trang, upload file, giữ lại dữ liệu form khi nhập sai.

## Bước 0. Hạ tầng dùng chung
| Việc | File |
|---|---|
| `Validator`: rule `required`, `min`, `max`, `numeric`, `integer`, `email`, `phone`, `alpha_dash`, `in`, `same`, `unique:table,col,ignoreId`, `exists:table,col` | `app/Core/Validator.php` |
| Lưu lỗi + dữ liệu cũ qua redirect; helper `old()`, `error()`, `invalid()`; `Controller::validate()` | `Session.php`, `helpers.php`, `Controller.php` |
| `Paginator`: offset/limit, HTML Bootstrap, giữ query string tìm kiếm | `app/Core/Paginator.php` |
| `Uploader`: kiểm MIME thật bằng `finfo`, ≤ 2MB, tên ngẫu nhiên, chặn chạy script trong thư mục upload | `app/Core/Uploader.php`, `public/assets/uploads/.htaccess` |
| Partial dùng chung: ô nhập liệu, thanh tìm kiếm, nút xóa, phân trang (`partial()` helper) | `app/Views/partials/` |
| Menu có nhóm "Danh mục"; route tạm cho `/products`, `/categories`, `/units`, `/suppliers` | `layouts/main.php`, `routes.php` |

## Bước 1. Đơn vị tính (`/units`) — làm mẫu
- `Unit` model, `UnitController`: `index`, `create`, `store`, `edit`, `update`, `destroy`.
- Route (vai trò `admin`): `GET /units`, `GET /units/create`, `POST /units`, `GET /units/{id}/edit`, `POST /units/{id}`, `POST /units/{id}/delete`.
- Validate: bắt buộc, ≤ 50 ký tự, unique.
- Xóa: nếu có sản phẩm đang dùng → flash lỗi "Đơn vị đang được dùng bởi N sản phẩm".
- View `units/index` và `units/form` (dùng chung thêm/sửa).

## Bước 2. Loại hàng (`/categories`)
Cùng khuôn bước 1, thêm `description`; cột "Số sản phẩm" bằng `LEFT JOIN ... COUNT`.

## Bước 3. Nhà cung cấp (`/suppliers`)
- Trường: `contact`, `phone`, `email`, `address`; validate phone, email.
- Tìm theo tên/SĐT + phân trang (lần đầu dùng `Paginator`).
- Đã có phiếu nhập → không xóa, chuyển sang ngừng hợp tác (`is_active=0`).

## Bước 4. Sản phẩm (`/products`)
Phụ thuộc bước 1–3 (dropdown loại hàng, đơn vị).
1. Danh sách: tìm theo tên/SKU/barcode; lọc loại hàng, trạng thái; phân trang 10 dòng; ảnh nhỏ; badge trạng thái.
2. Form thêm/sửa: SKU, barcode, tên, loại, đơn vị, giá bán, tồn tối thiểu, ảnh. SKU/barcode unique (bỏ qua chính nó khi sửa); barcode trống lưu `NULL`. Thay ảnh thì xóa file cũ.
3. Khóa/mở khóa thay vì xóa; chỉ xóa hẳn khi chưa có lô/đơn hàng.
4. `GET /products/search?q=` trả JSON (tuần 4 POS dùng lại).

## Bước 5. Tài khoản nhân viên (`/users`)
- Danh sách: vai trò, trạng thái, lần đăng nhập cuối.
- Thêm: username (unique, chữ/số/gạch dưới), họ tên, vai trò, SĐT, email, mật khẩu (≥ 6 ký tự, nhập 2 lần) → `password_hash`.
- Sửa: không đổi username; mật khẩu trống = giữ nguyên. Admin đặt lại mật khẩu được.
- Khóa/mở khóa. Chặn: admin tự khóa mình, tự hạ vai trò mình, khóa/hạ quyền admin cuối cùng.

## Bước 6. Hoàn thiện
- Gỡ các route tạm trong `PlaceholderController`; chỉ còn `inventory`, `pos`, `reports`.
- Bổ sung `seed.sql` (vài NCC, đơn vị) nếu cần.
- Viết test case Tuần 2 vào `docs/test-cases.md`; tick tiến độ trong `README.md`.

## Test case chính
| # | Kịch bản | Kỳ vọng |
|---|---|---|
| 1 | Thêm đơn vị trùng tên | Báo lỗi, giữ lại dữ liệu đã nhập |
| 2 | Xóa loại hàng đang có sản phẩm | Bị chặn, có thông báo |
| 3 | Thêm 2 sản phẩm cùng SKU / cùng barcode | Báo lỗi unique |
| 4 | Tạo 2 sản phẩm đều để trống barcode | Cả 2 thành công |
| 5 | Upload file `.php` đổi đuôi `.jpg` | Bị từ chối (kiểm MIME thật) |
| 6 | Tên sản phẩm chứa `<script>` | Hiển thị nguyên văn, không chạy |
| 7 | `kho1` / `thungan1` gõ `/products`, `/users` | 403 |
| 8 | Admin khóa chính mình / khóa admin cuối | Bị chặn |
| 9 | Nhân viên bị khóa đăng nhập | Thất bại |
| 10 | Xóa thiếu `_csrf` | 419 |
| 11 | Tìm kiếm/phân trang với `' OR 1=1 --` | Không lỗi SQL, không lộ dữ liệu |

## Thứ tự và phụ thuộc
```
Bước 0 (Validator, Paginator, Uploader, partial, menu)
   └─> Bước 1 Đơn vị ─> Bước 2 Loại hàng ─> Bước 3 Nhà cung cấp
                                                 └─> Bước 4 Sản phẩm
                                                        └─> Bước 5 Nhân viên ─> Bước 6 Hoàn thiện
```
Bước 1–3 độc lập, có thể đảo thứ tự. Bước 4 bắt buộc sau bước 1–2. Bước 3 chỉ cần xong trước Tuần 3 (nhập kho).

## Tiến độ
- [x] Bước 0
- [x] Bước 1 · [x] Bước 2 · [x] Bước 3 · [x] Bước 4 · [x] Bước 5 · [x] Bước 6
