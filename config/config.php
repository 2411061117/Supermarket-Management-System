<?php
// Cấu hình chung. Sửa thông tin DB cho khớp với XAMPP của bạn.
return [
    'app' => [
        'name'     => 'Quản lý cửa hàng tiện lợi',
        'debug'    => true,              // false khi nộp/triển khai
        'timezone' => 'Asia/Ho_Chi_Minh',
        'store_id' => 1,                 // chi nhánh mặc định
    ],
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'database' => 'convenience_store',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],
];
