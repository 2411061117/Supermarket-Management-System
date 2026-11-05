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
