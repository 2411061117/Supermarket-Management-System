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

## Tuần 3 — Tồn kho (`/inventory`)

Đăng nhập `admin` hoặc `kho1` (`123456`). Cần có vài lô trong `product_batches` để thấy cảnh báo (Bước 5 sẽ thêm vào seed).

| # | Thao tác | Kết quả mong đợi |
|---|----------|------------------|
| 1 | Mở `/inventory` khi chưa có lô nào | Liệt kê mọi sản phẩm đang bán, tồn 0, nhãn "Hết hàng"; thẻ "Hết hàng" = số sản phẩm |
| 2 | Sản phẩm có lô xa hạn + lô sắp hết hạn + lô đã hết hạn | Tồn bán được = lô xa + lô sắp hết hạn (không tính lô đã hết hạn); hiện "Sắp hết hạn: x" và "Hết hạn: y" |
| 3 | Sản phẩm chỉ có lô đã hết hạn | Tồn 0, nhãn "Hết hàng" + "Hết hạn: y" |
| 4 | Lô không HSD; lô có HSD đúng hôm nay; lô HSD đúng +7 ngày | Không HSD không bao giờ cảnh báo; hôm nay vẫn còn hạn; +7 ngày tính là sắp hết hạn |
| 5 | Tồn ≤ tồn tối thiểu (còn hàng) | Nhãn "Tồn thấp" |
| 6 | Bấm thẻ cảnh báo / lọc theo tình trạng (tồn thấp, hết hàng, đủ hàng, sắp hết hạn, đã hết hạn), loại hàng | Danh sách lọc đúng, số thẻ khớp số dòng; giá trị lạ (`status=bogus`, `category_id=abc`, tham số dạng mảng) coi như không lọc |
| 7 | Tìm `%`, `_`, `' OR 1=1 --`, `<script>` | Coi là ký tự thường, không lỗi SQL, không chạy script |
| 8 | Sản phẩm đã ngừng bán: còn hàng / hết sạch | Còn hàng thì vẫn hiện kèm nhãn "Ngừng bán"; hết sạch thì ẩn |
| 9 | Bấm "Xem lô" | Các lô xếp FEFO (HSD gần nhất trước, không HSD cuối); nhãn Còn hạn / Sắp hết hạn / Đã hết hạn; lô đã hết hàng bị ẩn |
| 10 | Chi tiết lô `?all=1` | Hiện cả lô đã hết hàng (nhãn "Đã hết hàng") |
| 11 | Mã lô chứa `<script>`; lô của chi nhánh khác | Mã lô được escape; lô chi nhánh khác không hiện |
| 12 | `/inventory/products/abc`, `/inventory/products/99999` | **404** |
| 13 | 13+ sản phẩm: trang cuối, `page=999`, lọc rồi sang trang 2 | 10 dòng/trang, trang cuối đúng, page sai được kẹp, link giữ `status`, `q`, `category_id` |
| 14 | `thungan1` vào `/inventory`, `/inventory/products/1`; chưa đăng nhập | **403**; chưa đăng nhập về `/login` |
| 15 | Dashboard | "Tồn kho thấp" không tính lô hết hạn; admin/kho1 có link "Xem tồn kho", thu ngân không có |

## Tuần 3 — Phiếu nhập kho: xem (`/receipts`)

Đăng nhập `admin` hoặc `kho1` (`123456`). Cần có phiếu trong DB (Bước 3 sẽ cho lập phiếu từ giao diện).

| # | Thao tác | Kết quả mong đợi |
|---|----------|------------------|
| 1 | Mở `/receipts` khi chưa có phiếu | Thông báo "Chưa có phiếu nhập nào" |
| 2 | Có nhiều phiếu | Mới nhất trước; mỗi dòng có mã, ngày `d/m/Y H:i`, NCC, người lập, số dòng, tổng giá trị (Σ giá nhập × SL); phiếu chi nhánh khác không hiện |
| 3 | Lọc theo mã phiếu (một phần), NCC | Đúng kết quả; `supplier_id=abc` coi như không lọc; NCC không tồn tại → "Không tìm thấy phiếu nhập phù hợp" |
| 4 | Lọc theo khoảng ngày: cùng một ngày; chỉ từ / chỉ đến | Gồm cả phiếu lập lúc 23:59:59 của ngày `to` |
| 5 | Khoảng ngày bị ngược (từ > đến) | Tự đổi chỗ + thông báo, vẫn ra kết quả |
| 6 | Ngày sai (`abc`, `2026-02-30`), tham số dạng mảng | Bỏ qua, không lỗi/warning |
| 7 | Tìm `%`, `' OR 1=1 --`, `<script>` | Coi là ký tự thường, không lỗi SQL, không chạy script |
| 8 | Bấm "Xem" | Thông tin phiếu (ngày, NCC + SĐT, người lập, ghi chú đã escape; trống → "—"); bảng dòng: SKU, sản phẩm, mã lô, HSD (`—` nếu không có), SL + ĐVT, giá nhập, thành tiền; tổng cộng đúng |
| 9 | Phiếu không có dòng | Thông báo "Phiếu không có dòng nào", tổng 0 ₫ |
| 10 | `/receipts/abc`, `/receipts/1.5`, `/receipts/99999`, phiếu của chi nhánh khác | **404** |
| 11 | `/receipts/{id}/edit`, `/receipts/{id}/delete` (GET và POST) | **404** — phiếu là chứng từ, không sửa/xóa; dữ liệu còn nguyên |
| 12 | 15 phiếu: trang 1, trang 2, `page=999`, `-3`; lọc rồi sang trang 2 | 10/5 dòng; page sai được kẹp; link phân trang giữ `supplier_id`, `from`… |
| 13 | `thungan1` vào `/receipts*`; chưa đăng nhập | **403**; chưa đăng nhập về `/login` |

## Tuần 3 — Lập phiếu nhập kho (`/receipts/create`)

Đăng nhập `kho1` hoặc `admin` (`123456`). Sau mỗi ca "bị từ chối" cần kiểm tra thêm: không có phiếu, lô hay dòng phiếu nào được lưu.

| # | Thao tác | Kết quả mong đợi |
|---|----------|------------------|
| 1 | Mở form | Có NCC đang hợp tác (không có NCC ngừng), sản phẩm đang bán (không có SP ngừng bán), 1 dòng trống, nút "Thêm dòng", ô HSD có `min` = hôm nay |
| 2 | Lập phiếu 3 dòng (2 sản phẩm, 1 sản phẩm có 2 lô; 1 dòng không HSD; 1 dòng HSD hôm nay) | Chuyển tới chi tiết phiếu, báo "Đã lập phiếu nhập PN-… (3 dòng, tổng …)"; DB: 1 phiếu, 3 lô, 3 dòng; `qty_received = qty_remaining`; người lập, chi nhánh, ghi chú đúng; tồn kho tăng đúng |
| 3 | Lập phiếu thứ 2 trong ngày | Mã tăng `-002` |
| 4 | Giá nhập 0; mã lô trùng nhưng ở sản phẩm khác; biên (ghi chú 255, mã lô 50, SL 1.000.000, giá 999.999.999); khoảng trắng đầu/cuối | Được chấp nhận; khoảng trắng bị cắt |
| 5 | Chưa chọn / không tồn tại / là chữ / đã ngừng hợp tác NCC; ghi chú 256 ký tự | Báo lỗi, không lưu |
| 6 | Không có dòng nào; `items` gửi sai kiểu; dòng trống hết; trường là mảng | Báo lỗi ("ít nhất 1 dòng"…), không lỗi PHP |
| 7 | Dòng: chưa chọn SP / SP không tồn tại / SP ngừng bán | Báo lỗi đúng dòng |
| 8 | Mã lô trống, 51 ký tự | Báo lỗi |
| 9 | HSD quá khứ, sai định dạng (`31/12/2030`), không tồn tại (`2030-02-30`) | Báo lỗi |
| 10 | SL = 0, âm, chữ, thập phân, `1e3`, > 1.000.000 | Báo lỗi |
| 11 | Giá nhập âm, chữ, > 999.999.999 | Báo lỗi |
| 12 | Mã lô đã có (kể cả khác hoa/thường); trùng mã lô giữa 2 dòng cùng sản phẩm | Báo "đã tồn tại" / "trùng với dòng 1" |
| 13 | 51 dòng | Báo "tối đa 50 dòng" |
| 14 | Dòng 1 hợp lệ, dòng 2 lỗi | Cả phiếu bị từ chối, dòng 1 không bị lưu mồ côi |
| 15 | Sau khi lỗi | Form giữ NCC, ghi chú, đủ số dòng và dữ liệu từng dòng (kể cả `<i>` được escape); chỉ ô sai bị tô đỏ kèm thông báo đúng dòng; ô mẫu không dính dữ liệu cũ; tải lại thì dữ liệu cũ biến mất |
| 16 | Ghi chú/mã lô chứa `<script>`, `x' OR '1'='1` | Lưu nguyên văn, hiển thị escape, không lỗi SQL, bảng còn nguyên |
| 17 | Thiếu / sai `_csrf`; `thungan1` POST; chưa đăng nhập | 419 / 403 / về `/login`; không lưu gì |
| 18 | Gửi lại y hệt lần trước (bấm lưu 2 lần, F5) | Lần 2 bị chặn do trùng mã lô, không có phiếu thứ 2; nút Lưu bị khóa sau khi bấm |
| 19 | Lỗi CSDL giữa chừng (giả lập bằng trigger lỗi ở dòng 2) | Rollback: không còn phiếu, lô, dòng nào (kể cả dòng 1) |
| 20 | 6 người lập phiếu cùng lúc: mã lô khác nhau / cùng mã lô | 6 phiếu, 6 mã phiếu khác nhau / chỉ đúng 1 phiếu được lưu; mọi lô đều có dòng phiếu |
| 21 | Trên form: gõ SL và giá, thêm/xóa dòng | "Tổng giá trị nhập" tự cập nhật; ô không hợp lệ bị bỏ qua |

## Tuần 3 — Ca làm việc (`/shifts`)

Đăng nhập `thungan1` hoặc `admin` (`123456`). Đóng ca/đối chiếu tiền mặt làm ở Tuần 4.

| # | Thao tác | Kết quả mong đợi |
|---|----------|------------------|
| 1 | `kho1` vào `/shifts`, POST `/shifts/open`; chưa đăng nhập | **403**, không tạo ca; chưa đăng nhập về `/login`; `kho1` không thấy nút trạng thái ca trên thanh trên |
| 2 | Thu ngân vào khi chưa có ca | Thẻ "Chưa mở ca", form (tiền đầu ca mặc định 0, ghi chú), "Chưa có ca nào"; thanh trên có nút vàng "Chưa mở ca" |
| 3 | Mở ca: tiền đầu ca ` 500000 `, ghi chú | Báo "Đã mở ca với tiền đầu ca 500.000 ₫"; DB: đúng chi nhánh/người, `status = open`, chưa có `closed_at`; thẻ "Đang mở" + giờ mở + "đã … phút"; form biến mất; thanh trên "Ca mở từ HH:MM" ở mọi trang |
| 4 | Mở ca khi đã có ca mở | Bị chặn, báo "đang có một ca mở", không tạo ca thứ 2, ca cũ không đổi |
| 5 | Tiền đầu ca: trống, âm, chữ, `1.5`, `1.000`, `1e3`, dạng mảng, thiếu trường, > 999.999.999 | Báo lỗi đúng, không tạo ca |
| 6 | Ghi chú 256 ký tự | Báo lỗi; biên 255 ký tự, tiền 0 và 999.999.999 được chấp nhận |
| 7 | Form lỗi | Giữ giá trị đã nhập (ghi chú được escape), tải lại thì biến mất |
| 8 | Ghi chú `<script>…`, `' OR 1=1 --` | Lưu nguyên văn, hiển thị escape, không lỗi SQL |
| 9 | Thiếu/sai `_csrf`; GET `/shifts/open`; `/shifts/1` | 419 (không tạo ca); 404; 404 |
| 10 | Thu ngân xem danh sách, kể cả thêm `?user_id=<admin>` | Chỉ thấy ca của chính mình, không lộ tên người khác, không có ô lọc nhân viên |
| 11 | Admin xem danh sách | Thấy mọi ca kèm tên + username; lọc nhân viên, trạng thái, khoảng ngày mở ca; giá trị lạ (`user_id=abc`, `status=bogus`, dạng mảng, ngày sai) coi như không lọc |
| 12 | Khoảng ngày ngược | Tự đổi chỗ + thông báo |
| 13 | Ca đã đóng / ca dưới 1 giờ / ca đang mở 125 phút | Hiển thị giờ đóng, tiền cuối ca, nhãn "Đã đóng"; "45 phút"; "2 giờ 5 phút" (tính theo đồng hồ CSDL) |
| 14 | 22 ca: trang 1, trang cuối, `page=999`, `-3`; lọc rồi sang trang 2 | 10 dòng/trang; page sai được kẹp; link giữ bộ lọc |
| 15 | 6 request mở ca đồng thời của cùng một người | Đúng 1 ca được tạo; không ai có 2 ca đang mở |

## Tuần 3 — Dữ liệu mẫu tồn kho (`database/seed.sql`)

Import lại `schema.sql` rồi `seed.sql` (ngày hết hạn tính tương đối so với hôm nay nên cảnh báo luôn đúng).

| # | Kiểm tra | Kết quả mong đợi |
|---|----------|------------------|
| 1 | `/inventory` ngay sau khi import | 5 sản phẩm; thẻ Hết hàng = 1 (Sữa tươi), Tồn thấp = 2 (Mì ly + Sữa), Sắp hết hạn = 1 (Coca), Đã hết hạn = 1 (Coca) |
| 2 | Coca-Cola (DU002) | Tồn bán được 40 (10 lon lô đã hết hạn không tính); "Sắp hết hạn: 40", "Hết hạn: 10"; chi tiết lô xếp FEFO: CC-2412 trước CC-2501 |
| 3 | Mì ly (TP001) | Tồn 12 ≤ tối thiểu 20 → "Tồn thấp" |
| 4 | Khăn giấy (HM001) | 50, lô không HSD → "Đủ hàng", không cảnh báo hạn |
| 5 | `/receipts` | 2 phiếu `PN-…-001` (tổng 1.110.000 ₫ và 385.000 ₫), mỗi lô có đúng 1 dòng phiếu |
| 6 | Dashboard | Tồn kho thấp: 2 sản phẩm; "Hàng sắp hết hạn" liệt kê CC-2501 và CC-2412 |
