# CHƯƠNG 3: Xây dựng và triển khai phần mềm

Chương này trình bày phiên bản website quản lý cửa hàng tiện lợi hiện có trong project, từ công nghệ, cấu trúc mã nguồn đến các nghiệp vụ đã được hiện thực. Nội dung được đối chiếu với mã nguồn PHP, cấu hình route, lược đồ cơ sở dữ liệu và dữ liệu mẫu. Ảnh minh họa và kết quả kiểm thử cần được bổ sung sau khi chạy ứng dụng thực tế.

## 3.1. Tổng quan website đã xây dựng

### 3.1.1. Công nghệ và tổ chức mã nguồn

Ứng dụng được xây dựng bằng PHP 8 thuần theo mô hình MVC, MySQL/MariaDB, HTML, CSS, JavaScript và Bootstrap 5. Apache được cung cấp qua XAMPP; PDO được sử dụng để truy cập cơ sở dữ liệu. Mã nguồn được tổ chức thành các thư mục: `public` chứa điểm vào và tài nguyên; `app/Core` chứa thành phần nền tảng; `app/Controllers` xử lý yêu cầu; `app/Models` truy vấn và thao tác dữ liệu nghiệp vụ; `app/Views` chứa giao diện; `config` chứa cấu hình và khai báo route; `database` chứa lược đồ và dữ liệu khởi tạo; `docs` chứa kế hoạch và kịch bản kiểm thử.

*Hình 3.1. Cấu trúc thư mục mã nguồn của website (bổ sung ảnh chụp thư mục project).* 

### 3.1.2. Cơ sở dữ liệu đã cài đặt

Cơ sở dữ liệu có tên `convenience_store`, được tạo từ `database/schema.sql`. Project cung cấp `database/seed.sql` để nạp dữ liệu minh họa. Lược đồ gồm 16 bảng thuộc các nhóm: cửa hàng và phân quyền; danh mục hàng hóa; sản phẩm; lô hàng và phiếu nhập; ca làm việc; khách hàng; hóa đơn và thanh toán. Tồn kho được theo dõi qua số lượng còn lại trong từng lô, thay vì lưu một số tồn tổng độc lập trong bảng sản phẩm.

*Hình 3.2. Các bảng trong cơ sở dữ liệu convenience_store (bổ sung ảnh chụp phpMyAdmin).*  
*Hình 3.3. Dữ liệu mẫu sau khi nạp seed.sql (bổ sung ảnh chụp).* 

## 3.2. Giao diện và kết quả chạy thử

Các chức năng dưới đây được mô tả theo route và mã xử lý hiện có. Vai trò được kiểm tra ở phía máy chủ. Các chú thích ảnh chỉ là vị trí đề xuất; cần thay bằng ảnh chụp ứng dụng đang chạy trên localhost.

### 3.2.1. Đăng nhập và phân quyền (F01 – UC01)

Chức năng đăng nhập xác thực tài khoản đang hoạt động và tạo phiên làm việc. Các route được giới hạn theo ba vai trò `admin`, `cashier` và `warehouse`.

*Hình 3.4. Màn hình đăng nhập.*  
*Hình 3.5. Trang tổng quan sau đăng nhập.*  
*Hình 3.6. Menu chức năng theo vai trò.*  
*Hình 3.7. Thông báo khi đăng nhập không hợp lệ hoặc truy cập sai quyền.*

`AuthController` kiểm tra tên đăng nhập, trạng thái tài khoản và mật khẩu đã băm. Thông tin phiên được dùng để xác định người dùng và vai trò. Quyền được kiểm tra tại route/backend, do đó việc truy cập trực tiếp một URL không thuộc vai trò sẽ bị từ chối. Admin quản lý dữ liệu và báo cáo; thu ngân sử dụng POS, hóa đơn và ca; nhân viên kho truy cập kho và phiếu nhập.

### 3.2.2. Quản lý danh mục (F02 – UC02)

Nhóm chức năng quản trị gồm loại hàng, đơn vị tính, nhà cung cấp, sản phẩm và tài khoản nhân viên. Admin có thể tra cứu, thêm, sửa và thay đổi trạng thái các dữ liệu được hỗ trợ.

*Hình 3.8. Danh sách loại hàng.*  
*Hình 3.9. Danh sách đơn vị tính.*  
*Hình 3.10. Danh sách nhà cung cấp.*  
*Hình 3.11. Danh sách sản phẩm và thao tác quản trị.*

Sản phẩm có SKU duy nhất, mã vạch tùy chọn, tên, loại hàng, đơn vị tính, giá bán, mức tồn tối thiểu và ảnh. Dữ liệu được kiểm tra trước khi lưu. Sản phẩm đã phát sinh lô và nhà cung cấp đã có phiếu nhập được ngừng bán/ngừng hợp tác thay vì xóa để bảo toàn liên kết lịch sử. Admin cũng có thể quản lý tài khoản nhân viên và trạng thái hoạt động.

### 3.2.3. Nhập kho (F03 – UC03)

Nhân viên kho hoặc admin lập phiếu nhập gắn với nhà cung cấp. Một phiếu có thể có nhiều dòng; mỗi dòng ghi nhận một lô hàng với giá nhập, số lượng và hạn sử dụng nếu có.

*Hình 3.12. Biểu mẫu lập phiếu nhập.*  
*Hình 3.13. Chi tiết phiếu nhập đã lưu.*  
*Hình 3.14. Tồn kho và thông tin lô hàng.*

`ReceiptController` tiếp nhận dữ liệu và model `GoodsReceipt` lưu chứng từ cùng các lô `product_batches`. Tồn của sản phẩm được tổng hợp từ số lượng còn lại ở các lô. Màn hình kho hỗ trợ theo dõi tồn thấp và tình trạng hạn sử dụng. Phiếu nhập đã tạo được xem lại, không có luồng sửa hoặc xóa chứng từ trên giao diện.

### 3.2.4. Bán hàng POS (F04 – UC04)

POS dành cho admin và thu ngân có ca đang mở. Nhân viên tìm sản phẩm theo tên, SKU hoặc mã vạch, thêm sản phẩm vào giỏ và gửi yêu cầu thanh toán.

*Hình 3.15. Màn hình bán hàng POS.*  
*Hình 3.16. Tìm kiếm sản phẩm và giỏ hàng.*  
*Hình 3.17. Hóa đơn sau khi thanh toán.*

Máy chủ tự xác định giá, tổng tiền và phân bổ hàng từ các lô còn hạn theo FEFO (lô gần hết hạn được xuất trước). Nếu tồn không đủ hoặc dữ liệu không hợp lệ, đơn không được ghi nhận. Việc tạo hóa đơn, chi tiết hóa đơn, thanh toán và trừ tồn được thực hiện trong transaction. Hệ thống lưu mã yêu cầu checkout trong session để ngăn gửi lặp cùng yêu cầu tạo thêm đơn. Hóa đơn có thể xem và in; admin có quyền hủy hóa đơn, khi hủy số lượng được hoàn lại các lô liên quan.

### 3.2.5. Thanh toán và khách hàng (F05, F06)

POS hỗ trợ tra cứu khách hàng theo số điện thoại, chọn phương thức thanh toán, nhập số tiền khách đưa khi thanh toán tiền mặt và tính tiền thừa. Điểm khách hàng được cập nhật khi giao dịch hợp lệ; project không có màn hình quản lý hồ sơ khách hàng độc lập.

*Hình 3.18. Tra cứu khách hàng theo số điện thoại.*  
*Hình 3.19. Chọn phương thức thanh toán.*  
*Hình 3.20. Kết quả thanh toán và thông tin điểm.*

Luồng checkout hiện ghi nhận một phương thức thanh toán cho mỗi đơn. Phương thức được chọn từ danh mục đang hoạt động. Hệ thống đối chiếu số tiền thanh toán với tổng đơn, xử lý tiền mặt và tiền thừa khi phù hợp, đồng thời cập nhật điểm khách hàng nếu có thông tin hợp lệ. Không mô tả thanh toán chia nhiều phương thức như chức năng đã hoàn thiện.

### 3.2.6. Quản lý ca (F07 – UC05)

Thu ngân mở ca với số tiền đầu ca, theo dõi hoạt động bán hàng và đóng ca bằng số tiền mặt thực đếm. Thu ngân chỉ xem ca của mình; admin có thể xem và quản lý ca trong cửa hàng.

*Hình 3.21. Danh sách và trạng thái ca làm việc.*  
*Hình 3.22. Chi tiết ca và đối soát tiền mặt.*

Tiền mặt dự kiến khi đóng ca được tính từ tiền đầu ca cộng các khoản thanh toán tiền mặt của hóa đơn hợp lệ thuộc ca. Chênh lệch bằng tiền thực đếm trừ tiền dự kiến. Hệ thống lưu trạng thái, thời điểm đóng, số tiền thực tế và số tiền dự kiến để phục vụ tra cứu.

### 3.2.7. Báo cáo thống kê (F08 – UC06)

Admin có thể lọc khoảng ngày và xem doanh thu theo ngày, phương thức thanh toán, thu ngân và sản phẩm bán chạy theo số lượng hoặc doanh thu.

*Hình 3.23. Báo cáo doanh thu và thống kê sản phẩm.*

Báo cáo mặc định hiển thị bảy ngày gần nhất, giới hạn khoảng ngày tối đa 366 ngày và điều chỉnh khoảng ngày nhập ngược. Các truy vấn chỉ tính đơn `completed`, loại đơn `cancelled`. Báo cáo hiện có thống kê doanh thu và sản phẩm; không hiển thị lợi nhuận gộp như một chỉ số trên giao diện.

Ngoài các chức năng trên, project có trang tổng quan, tra cứu tồn kho theo sản phẩm, quản lý tài khoản, danh sách/chi tiết/in hóa đơn. Có thể bổ sung ảnh các màn hình này sau khi chụp từ bản chạy thực tế.

## 3.3. Kiểm thử chức năng

`docs/test-cases.md` mô tả các kịch bản kiểm thử của project; Bảng 2.12 trong báo cáo là bộ tình huống tham chiếu. Cần chạy từng tình huống trên ứng dụng cài đặt thực tế, ghi kết quả quan sát được vào cột “Kết quả thực tế” và chỉ đánh dấu đạt/không đạt sau khi đã chạy. Việc rà soát mã nguồn không thay thế cho kết quả kiểm thử thực tế.

**Bảng 3.1. Kết quả kiểm thử chức năng của website.** Điền kết quả thực tế và trạng thái sau khi chạy các TC01–TC10 tương ứng trong Bảng 2.12; bổ sung số hình minh chứng nếu có.

## 3.4. Hướng dẫn cài đặt và chạy thử

1. Đặt thư mục project tại `C:\xampp\htdocs\Supermarket-Management-System`.
2. Bật Apache và MySQL trong XAMPP.
3. Mở phpMyAdmin, chạy `database/schema.sql`, sau đó chạy `database/seed.sql` để nạp dữ liệu mẫu.
4. Kiểm tra thông tin kết nối và base path trong `config/config.php` theo môi trường máy đang sử dụng.
5. Mở `http://localhost/Supermarket-Management-System/public/` trên trình duyệt.

Project yêu cầu PHP 8.1 trở lên và MySQL 8 hoặc MariaDB tương thích. Tài khoản mẫu (mật khẩu `123456`) được ghi trong README: `admin` (quản trị), `thungan1` (thu ngân), `kho1` (nhân viên kho). Chỉ sử dụng tài khoản và dữ liệu seed cho mục đích trình diễn.

**Bảng 3.2. Tài khoản dùng thử:** ghi lại các tài khoản trên, vai trò tương ứng và phạm vi chức năng theo README của project.
