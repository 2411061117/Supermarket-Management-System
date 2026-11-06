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

## Tuần 2 — Nhà cung cấp (`/suppliers`)
| # | Bước | Kết quả mong đợi |
|---|---|---|
| 1 | Đăng nhập `admin`, vào **Danh mục → Nhà cung cấp** | Thấy 2 NCC mẫu kèm badge "Đang hợp tác"; NCC đã có phiếu nhập có nút Xóa bị khóa |
| 2 | Tìm "á châu" (gõ thường, có dấu) | Chỉ còn 1 NCC "Thực phẩm Á Châu"; ô tìm giữ từ khóa, xuất hiện nút "Xóa lọc" |
| 3 | Tìm theo SĐT `0912000002`, người liên hệ `Minh`, email `acfood` | Mỗi lần ra đúng 1 NCC |
| 4 | Tìm `zzzz` | Bảng báo "Không tìm thấy nhà cung cấp phù hợp" |
| 5 | Tìm `%` hoặc `_` | 0 kết quả (hiểu là ký tự thật, không khớp tất cả) |
| 6 | Tìm `' OR 1=1 -- ` và `"><script>alert(1)</script>` | Không lỗi SQL, 0 kết quả, không chạy script |
| 7 | Thêm NCC chỉ nhập tên | Thành công; 4 trường còn lại lưu `NULL`; mặc định "Đang hợp tác" |
| 8 | Thêm NCC có SĐT `0912 345 678`, `0912.345.679`, `0912-345-680`, `+84912345681` | Lưu chuẩn hóa, bỏ dấu cách/chấm/gạch (`0912345678`, ...) |
| 9 | SĐT `abc`, `123`, `09123`, `+1912345678`; email `khong-hop-le` | Báo "không đúng định dạng"; giữ lại các giá trị khác đã nhập; không lưu |
| 10 | Tên rỗng; tên 151 ký tự; liên hệ 101; địa chỉ 256 | Báo "không được để trống" / "tối đa 150" / "tối đa 100" / "tối đa 255". Tên đúng 150 ký tự được chấp nhận |
| 11 | Thêm NCC tên `<script>alert(1)</script>`, địa chỉ `"><img src=x onerror=alert(2)>` | Hiển thị nguyên văn ở danh sách và form sửa, không chạy |
| 12 | Sửa NCC: đổi dữ liệu; rồi sửa sai (tên rỗng) | Lần 1 lưu được; lần 2 quay lại form sửa kèm lỗi, dữ liệu cũ không đổi; sửa không làm đổi trạng thái hợp tác |
| 13 | Bấm **Ngừng** trên 1 NCC, rồi **Kích hoạt** | Lần lượt báo "Đã ngừng hợp tác…" / "Đã kích hoạt lại…"; badge và nút đổi theo |
| 14 | Xóa NCC đã có phiếu nhập (POST trực tiếp) | Bị chặn, báo "đã có N phiếu nhập… dùng Ngừng hợp tác"; phiếu nhập còn nguyên; vẫn ngừng hợp tác được |
| 15 | Xóa NCC chưa có phiếu nhập | Xóa thành công |
| 16 | Có 21 NCC: xem trang 1, trang cuối, `?page=999`, `?page=0`, `?page=-3`, `?page=abc` | Trang 1 có 10 dòng + "Hiển thị 1–10 / 21"; trang cuối 1 dòng; page vượt/sai được kẹp về trang hợp lệ, không lỗi |
| 17 | Tìm "Phân trang" rồi sang trang 2 | Link phân trang giữ từ khóa; trang 2 có 3 dòng, "Hiển thị 11–13 / 13" |
| 18 | Lọc "Ngừng hợp tác" / "Đang hợp tác"; kết hợp với từ khóa; `?status=bogus`; `?q[]=x` | Lọc đúng; kết hợp được; giá trị lạ hoặc dạng mảng coi như không lọc, không warning |
| 19 | Mở `/suppliers/1/delete`, `/suppliers/1/toggle` bằng GET; `/suppliers/abc/edit`, `/suppliers/99999/edit` | Trang lỗi **404** |
| 20 | Xóa input `_csrf` rồi submit (thêm/sửa/ngừng/xóa) | Trang lỗi **419**, không đổi dữ liệu |
| 21 | `kho1` / `thungan1` vào mọi route `/suppliers*` (kể cả tìm kiếm), gửi POST thêm/sửa/ngừng/xóa | Trang lỗi **403**, không đổi dữ liệu; menu không có "Nhà cung cấp" |

## Tuần 2 — Sản phẩm (`/products`)
| # | Bước | Kết quả mong đợi |
|---|---|---|
| 1 | Đăng nhập `admin`, vào **Sản phẩm** | Thấy 5 sản phẩm mẫu kèm giá (vd `12.000 ₫`), mã vạch, loại hàng, ĐVT; chưa có ảnh thì hiện icon; menu "Danh mục" không mở |
| 2 | Tìm "coca", "du0", mã vạch đầy đủ, một phần mã vạch | Tìm được theo tên (không phân biệt hoa thường), SKU, mã vạch |
| 3 | Tìm `%`, `_`, `' OR 1=1 -- `, `"><script>…` | `%`/`_` hiểu là ký tự thật (0 kết quả); không lỗi SQL; không chạy script |
| 4 | Lọc loại hàng "Đồ uống"; lọc + từ khóa; `?category_id=abc`, `-1`, `999`; `?status=bogus`; `?q[]=x` | Lọc đúng; giá trị lạ/mảng coi như không lọc, không warning; loại không tồn tại → 0 dòng |
| 5 | Thêm sản phẩm nhập SKU `tp002` | SKU lưu thành `TP002`; barcode trống lưu `NULL`; 2 sản phẩm cùng không có barcode đều lưu được |
| 6 | Biên hợp lệ: giá `0` và `999999999`; tồn `0` và `1000000`; SKU 30 ký tự; barcode 30 chữ số | Được chấp nhận |
| 7 | SKU: trống / có dấu cách / có `-` / có chữ có dấu / 31 ký tự / trùng (`du001`, khác hoa-thường) | Báo lỗi tương ứng, không lưu |
| 8 | Barcode: có chữ / có dấu cách / âm / 31 chữ số / trùng | Báo "chỉ gồm chữ số" / "tối đa 30" / "đã tồn tại" |
| 9 | Tên trống/151 ký tự; chưa chọn hoặc chọn loại hàng/ĐVT không tồn tại hoặc nhập `1 OR 1=1` | Báo lỗi; không lưu |
| 10 | Giá: trống / `-5` / `abc` / `12.5` / `12.000` / `1000000000` / `99999999999999999999999`; tồn tối thiểu: `-1` / `1000001` / trống | Báo lỗi, không lưu và không gây lỗi tràn cột DB |
| 11 | Sau mỗi lần lỗi | Form giữ lại SKU, barcode, tên (đã escape), loại hàng, ĐVT, giá đã nhập |
| 12 | Thêm kèm ảnh PNG / WEBP / JPG hợp lệ | Lưu thành `uploads/products/<32 ký tự hex>.<đuôi>`; danh sách hiển thị ảnh; mở URL ảnh được (200, đúng `Content-Type`) |
| 13 | Upload: file PHP đổi đuôi `.jpg`; ảnh GIF; header PNG + dữ liệu rác; ảnh 3 MB | Báo "Chỉ chấp nhận ảnh…" hoặc "Ảnh quá lớn"; không lưu sản phẩm, không sinh file |
| 14 | Upload ảnh có tên file gốc `../../evil.php` | Vẫn lưu với tên ngẫu nhiên trong `uploads/products/`; không có file `evil*` |
| 15 | Form lỗi (vd trùng SKU) nhưng đã chọn ảnh hợp lệ | Không sinh file mồ côi (ảnh chỉ lưu sau khi dữ liệu hợp lệ) |
| 16 | Thêm sản phẩm tên `<script>alert(1)</script>` và `'; DROP TABLE products; --` | Hiển thị nguyên văn (kể cả form sửa, hộp thoại xác nhận), không chạy; bảng còn nguyên |
| 17 | Mở form sửa | Có sẵn SKU, barcode, loại hàng, ĐVT, giá cũ; có ảnh thì hiện ảnh + ô "Gỡ ảnh hiện tại" |
| 18 | Sửa mà giữ nguyên SKU/barcode; đổi SKU hoặc barcode thành của sản phẩm khác | Lần 1 thành công (bỏ qua chính nó) và không đổi trạng thái bán; lần 2 báo "đã tồn tại", dữ liệu cũ không đổi |
| 19 | Sửa: không chọn ảnh mới / chọn ảnh mới / chọn ảnh sai / tick "Gỡ ảnh" / vừa gỡ vừa chọn ảnh mới | Giữ ảnh cũ / thay ảnh và xóa file cũ / báo lỗi giữ ảnh cũ / ảnh = `NULL` và xóa file / dùng ảnh mới |
| 20 | Bấm **Ngừng bán**, rồi **Mở bán** | Báo "Đã ngừng bán…"; lọc "Ngừng bán" thấy sản phẩm; API tìm kiếm không trả sản phẩm ngừng bán; mở bán lại thì trả lại |
| 21 | Xóa sản phẩm đã có lô hàng | Bị chặn, báo "đã có N lô hàng… dùng Ngừng bán"; lô hàng còn nguyên; vẫn ngừng bán được |
| 22 | Xóa sản phẩm chưa có lô hàng (có ảnh) | Xóa thành công, file ảnh bị xóa theo |
| 23 | Có 26 sản phẩm: trang 1, trang cuối, `?page=999`, `0`, `-3`, `abc`; tìm + lọc loại rồi sang trang 2 | 10 dòng/trang; trang cuối đúng số dòng; page sai được kẹp; link phân trang giữ `q` và `category_id` |
| 24 | `GET /products/search?q=coca` | JSON `{"items":[…]}`, `Content-Type: application/json`; mỗi mục có id, sku, barcode, name, unit, sale_price (số nguyên), stock, image |
| 25 | `stock` trong kết quả search | Chỉ tính lô còn hạn hoặc không có HSD; lô hết hạn và sản phẩm chưa nhập lô cho `0` |
| 26 | Search: nhập đúng mã vạch của sản phẩm A, trong khi tên sản phẩm B cũng chứa dãy số đó | A (khớp chính xác barcode) xếp đầu |
| 27 | Search: `q` rỗng / thiếu / dạng mảng / `%` / `' OR 1=1 --` | `items` rỗng, JSON hợp lệ, không lỗi; có 25 sản phẩm khớp thì chỉ trả tối đa 20 |
| 28 | Search: tên sản phẩm có dấu nháy, `\`, `<b>` | JSON vẫn hợp lệ, giá trị đúng nguyên văn |
| 29 | `/products/{id}/delete`, `/products/{id}/toggle` bằng GET; `/products/abc/edit`, `/products/99999/edit` | **404** |
| 30 | Xóa input `_csrf` (kể cả form upload multipart) hoặc dùng token sai | Trang lỗi **419**, không đổi dữ liệu, không sinh file |
| 31 | `kho1` / `thungan1` vào `/products`, `/products/create`, `/products/1/edit`; POST thêm/sửa/ngừng bán/xóa | **403**, dữ liệu không đổi; menu không có "Sản phẩm" |
| 32 | `kho1` / `thungan1` gọi `/products/search?q=coca`; chưa đăng nhập gọi cùng URL | Hai vai trò này nhận JSON bình thường (cho POS và nhập kho); chưa đăng nhập bị chuyển về `/login` |

## Tuần 2 — Nhân viên (`/users`)

Tài khoản đăng nhập: `admin` / `123456`. Toàn bộ chức năng chỉ dành cho admin.

| # | Thao tác | Kết quả mong đợi |
|---|----------|------------------|
| 1 | Danh sách | Hiện 3 nhân viên seed, vai trò dạng nhãn màu, trạng thái, lần đăng nhập cuối (hoặc "Chưa đăng nhập"), nhãn "Bạn" ở dòng của mình; không lộ mật khẩu/hash |
| 2 | Tìm theo username / họ tên / SĐT / email; không phân biệt hoa thường; `%`, `_`, `' OR 1=1 --`, `<script>` | Tìm đúng; `%` `_` coi là ký tự thường; không lỗi SQL, không chạy script |
| 3 | Lọc theo vai trò, trạng thái; giá trị lạ (`role_id=abc`, `status=bogus`, dạng mảng) | Lọc đúng; giá trị lạ coi như không lọc, không warning |
| 4 | Thêm hợp lệ (username ` NV_Test1 `, SĐT `0912 345 678`) | Username lưu `nv_test1`, SĐT `0912345678`, mật khẩu lưu bcrypt (`$2y$…`), nhân viên đăng nhập được bằng mật khẩu đó |
| 5 | Thêm chỉ với trường bắt buộc | phone/email lưu `NULL` |
| 6 | Username: trống, 2 ký tự, 51 ký tự, có dấu cách/gạch ngang/chữ có dấu, kiểu SQLi, trùng (kể cả `ADMIN`) | Báo lỗi đúng từng trường hợp, không lưu; biên 3 và 50 ký tự được chấp nhận |
| 7 | Họ tên trống/101 ký tự; vai trò trống/không tồn tại/là chữ; SĐT, email sai | Báo lỗi, không lưu |
| 8 | Mật khẩu: trống, 5 ký tự, 73 ký tự, xác nhận không khớp/trống | Báo lỗi; biên 6 và 72 ký tự được chấp nhận |
| 9 | Form lỗi | Giữ lại dữ liệu đã nhập (đã escape), **không** in lại mật khẩu |
| 10 | Sửa: gửi kèm `username=hacked`, để trống mật khẩu | Username không đổi; mật khẩu cũ vẫn dùng được; email trống → `NULL` |
| 11 | Sửa: đặt lại mật khẩu (mới < 6, không khớp, bỏ trống xác nhận, hợp lệ) | 3 trường hợp đầu báo lỗi, không đổi gì; hợp lệ thì mật khẩu cũ hết hiệu lực, mật khẩu mới đăng nhập được |
| 12 | Gửi form sửa **không có** trường mật khẩu | Mật khẩu giữ nguyên (không bị ghi đè bằng hash rỗng) |
| 13 | Sửa chính mình, cố gửi `role_id` khác | Ô vai trò bị khóa; vai trò vẫn giữ nguyên; họ tên mới hiện ngay trên menu |
| 14 | Admin đổi vai trò nhân viên đang đăng nhập | Có hiệu lực ngay ở request kế tiếp, không cần đăng nhập lại |
| 15 | Khóa nhân viên đang đăng nhập | Request kế tiếp bị chuyển `/login` kèm 1 thông báo "tài khoản đã bị khóa"; đăng nhập mới bị từ chối bằng thông báo chung |
| 16 | Mở khóa | Đăng nhập lại được; flash "Đã mở khóa" |
| 17 | Tự khóa chính mình | Bị chặn, báo "không thể tự khóa tài khoản của chính mình" |
| 18 | Tài khoản bị xóa khỏi DB khi đang đăng nhập | Bị chuyển `/login` ngay |
| 19 | Phân trang với 24 nhân viên; `page=999/0/-3/abc`; tìm + lọc + trang 2 | 10 dòng/trang, trang cuối 4 dòng, page sai được kẹp, link giữ `q` và `role_id` |
| 20 | `/users/abc/edit`, `/users/99999/edit`, GET `/users/{id}/toggle` | **404** |
| 21 | Thiếu hoặc sai `_csrf` khi thêm/sửa/khóa | **419**, không đổi dữ liệu |
| 22 | `kho1` / `thungan1` vào mọi route `/users*` và POST thêm/sửa/khóa (cố tạo admin, đặt lại mật khẩu admin) | **403**, dữ liệu không đổi; menu không có "Nhân viên" |
| 23 | Hồi quy đăng nhập: sai mật khẩu, tài khoản không tồn tại, SQLi, đăng xuất | Báo lỗi chung / bị từ chối; sau đăng xuất vào `/users` bị chuyển `/login` |
