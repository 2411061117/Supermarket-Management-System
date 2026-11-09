# Web quản lý cửa hàng tiện lợi

Bài thực hành môn Phát triển phần mềm trên nền web — PHP 8 thuần (MVC) + MySQL + Bootstrap 5.

## Cài đặt (XAMPP)
1. Yêu cầu **PHP ≥ 8.1** (XAMPP 8.1+), bật `mod_rewrite` (mặc định đã bật).
2. Copy thư mục `BTHT6` vào `C:\xampp\htdocs\`.
3. Mở phpMyAdmin → tab **Import** → chạy lần lượt `database/schema.sql` rồi `database/seed.sql`.
4. Sửa `config/config.php` nếu MySQL có mật khẩu.
5. Truy cập: <http://localhost/BTHT6/public/>

### Tài khoản mẫu (mật khẩu `123456`)
| Username | Vai trò | Được vào |
|---|---|---|
| `admin` | Quản lý | Tất cả (kể cả Loại hàng, ĐVT, NCC, Sản phẩm, Nhân viên) |
| `thungan1` | Thu ngân | Tổng quan, POS, Ca làm việc |
| `kho1` | NV kho | Tổng quan, Kho hàng (tồn kho, phiếu nhập) |

## Cấu trúc
```
public/          Front controller (index.php), .htaccess, assets
app/Core/        Router, Database (PDO), Model, Controller, View, Auth, Session, Csrf, Validator, Paginator, Uploader
app/Controllers/ Xử lý request
app/Models/      Truy vấn CSDL (prepared statements)
app/Views/       Giao diện (layouts/, partials/, auth/, dashboard/, errors/, units/, categories/, suppliers/, products/, users/)
config/          config.php, routes.php (khai báo route + vai trò)
database/        schema.sql, seed.sql
docs/            Tài liệu phân tích thiết kế, test case
```

### Thêm 1 chức năng mới
1. Tạo model trong `app/Models/` (kế thừa `App\Core\Model`, đặt `$table`).
2. Tạo controller trong `app/Controllers/` (kế thừa `App\Core\Controller`).
3. Tạo view trong `app/Views/<module>/`; luôn xuất dữ liệu qua `e()`, form POST luôn có `<?= csrf_field() ?>`.
4. Khai báo route trong `config/routes.php` kèm danh sách vai trò.

## Tiến độ
- [x] Tuần 1: Khung MVC, CSDL, đăng nhập, phân quyền, dashboard
- [x] Tuần 2: CRUD loại hàng, ĐVT, nhà cung cấp, sản phẩm (upload ảnh, API tìm kiếm), tài khoản nhân viên
- [x] Tuần 3: Nhập kho theo lô/HSD (phiếu nhập nhiều dòng), tồn kho theo FEFO, cảnh báo tồn thấp/hết hạn, mở ca
- [ ] Tuần 4: POS (FEFO, thanh toán, in hóa đơn), đóng ca, báo cáo
