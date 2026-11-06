# Kịch bản kiểm thử

## Tuần 1 — Đăng nhập & phân quyền
| # | Bước | Kết quả mong đợi |
|---|---|---|
| 1 | Vào `/` khi chưa đăng nhập | Chuyển về `/login`, báo "Vui lòng đăng nhập" |
| 2 | Đăng nhập `admin` / `123456` | Vào trang Tổng quan, thấy đủ menu: Tổng quan, POS, Kho, Sản phẩm, nhóm Danh mục (3 mục con), Nhân viên, Báo cáo |
| 3 | Đăng nhập sai mật khẩu | Báo "Tên đăng nhập hoặc mật khẩu không đúng" |
| 4 | Đăng nhập `kho1`, gõ URL `/pos` | Trang lỗi **403** |
| 5 | Đăng nhập `thungan1`, gõ URL `/inventory` | Trang lỗi **403** |
| 6 | Username nhập `' OR 1=1 -- ` | Đăng nhập thất bại (chống SQL injection) |
| 7 | Xóa input `_csrf` trong form login bằng DevTools rồi submit | Trang lỗi **419** |
| 8 | Bấm Đăng xuất, nhấn Back của trình duyệt rồi tải lại | Quay về `/login` |
| 9 | Mở trên điện thoại (DevTools ~375px) | Menu thu vào nút ☰, bảng cuộn ngang trong khung |
| 10 | Gõ `http://localhost/BTHT6/config/config.php` | **403 Forbidden** |
| 11 | Dashboard: chưa có lô hàng nào | Cả 5 sản phẩm hiện trong "Tồn kho thấp" (tồn = 0) |

## Tuần 2 — Đơn vị tính (`/units`)
| # | Bước | Kết quả mong đợi |
|---|---|---|
| 1 | Đăng nhập `admin`, vào **Danh mục → Đơn vị tính** | Thấy 5 đơn vị mẫu kèm cột "Số sản phẩm"; nhóm menu Danh mục tự mở |
| 2 | Thêm đơn vị "Thùng" | Quay về danh sách, báo "Đã thêm đơn vị", có dòng mới |
| 3 | Thêm lại "Thùng" (hoặc "thùng") | Quay lại form, báo "đã tồn tại", ô nhập giữ nguyên giá trị; F5 thì thông báo lỗi biến mất |
| 4 | Để trống hoặc chỉ nhập khoảng trắng | Báo "không được để trống" |
| 5 | Nhập tên 51 ký tự | Báo "tối đa 50 ký tự", không lưu |
| 6 | Sửa "Thùng", bấm Lưu mà không đổi tên | Thành công (unique bỏ qua chính bản ghi đang sửa) |
| 7 | Sửa "Thùng" thành "Lon" | Báo "đã tồn tại", ở lại form sửa |
| 8 | Vào `/units/abc/edit`, `/units/99999/edit` | Trang lỗi **404** |
| 9 | Thêm đơn vị tên `<script>alert(1)</script>` | Hiển thị nguyên văn, không chạy script; xóa được bình thường |
| 10 | Thêm đơn vị tên `'; DROP TABLE units; --` | Lưu như chuỗi thường, bảng `units` còn nguyên |
| 11 | Xóa đơn vị "Chai" (đang có sản phẩm dùng) | Nút Xóa bị khóa trên giao diện; gửi POST trực tiếp vẫn bị chặn, báo "đang được dùng bởi N sản phẩm" |
| 12 | Xóa đơn vị chưa dùng | Xóa thành công |
| 13 | Mở `/units/1/delete` bằng GET | **404** (chỉ nhận POST) |
| 14 | Xóa input `_csrf` bằng DevTools rồi submit (thêm/sửa/xóa) | Trang lỗi **419**, không có thay đổi dữ liệu |
| 15 | `kho1` / `thungan1` vào `/units`, `/units/create`, `/units/1/edit`; gửi POST thêm/xóa | Trang lỗi **403**, không có thay đổi dữ liệu; menu không có "Đơn vị tính" |

## Tuần 2 — Loại hàng (`/categories`)
| # | Bước | Kết quả mong đợi |
|---|---|---|
| 1 | Đăng nhập `admin`, vào **Danh mục → Loại hàng** | Thấy 4 loại mẫu kèm mô tả và cột "Số sản phẩm" (Đồ uống = 2); nút Xóa bị khóa ở cả 4 dòng |
| 2 | Thêm "Bánh kẹo" kèm mô tả | Quay về danh sách, báo "Đã thêm loại hàng" |
| 3 | Thêm loại hàng để trống hoặc chỉ nhập khoảng trắng ở mô tả | Lưu thành công, mô tả là `NULL` trong DB (không phải chuỗi rỗng) |
| 4 | Thêm lại "Bánh kẹo" (hoặc "BÁNH KẸO") | Quay lại form, báo "đã tồn tại", giữ nguyên tên và mô tả đã nhập |
| 5 | Tên rỗng; tên 101 ký tự; mô tả 256 ký tự | Báo "không được để trống" / "tối đa 100" / "tối đa 255"; không lưu. Mô tả đúng 255 ký tự thì được chấp nhận |
| 6 | Sửa "Bánh kẹo", giữ nguyên tên, đổi mô tả; rồi xóa trắng mô tả | Thành công; mô tả trắng lưu thành `NULL` |
| 7 | Sửa "Bánh kẹo" thành "Sữa" | Báo "đã tồn tại", ở lại form sửa, tên trong DB không đổi |
| 8 | Vào `/categories/abc/edit`, `/categories/99999/edit` | Trang lỗi **404** |
| 9 | Thêm tên `<b>đậm</b>`, mô tả `<script>alert(1)</script>` | Hiển thị nguyên văn ở danh sách và trong ô sửa, không chạy script |
| 10 | Thêm tên `'; DROP TABLE categories; --` | Lưu như chuỗi thường, bảng còn nguyên |
| 11 | Xóa loại "Đồ uống" (gửi POST trực tiếp) | Bị chặn, báo "đang có 2 sản phẩm"; sản phẩm không bị ảnh hưởng |
| 12 | Xóa loại chưa có sản phẩm | Xóa thành công |
| 13 | Mở `/categories/1/delete` bằng GET | **404** (chỉ nhận POST) |
| 14 | Xóa input `_csrf` rồi submit (thêm/sửa/xóa) | Trang lỗi **419**, không đổi dữ liệu |
| 15 | `kho1` / `thungan1` vào mọi route `/categories*`, gửi POST thêm/sửa/xóa | Trang lỗi **403**, không đổi dữ liệu; menu không có "Loại hàng" |
