<?php
declare(strict_types=1);

// Front controller: mọi request đều đi qua file này
define('BASE_DIR', dirname(__DIR__));

// Autoload PSR-4 đơn giản: App\Core\Router -> app/Core/Router.php
spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = BASE_DIR . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});
require BASE_DIR . '/app/helpers.php';

use App\Core\App;
use App\Core\Router;
use App\Core\Session;

$config = require BASE_DIR . '/config/config.php';
App::boot($config);

ini_set('display_errors', $config['app']['debug'] ? '1' : '0');
error_reporting(E_ALL);

Session::start();

$router = new Router();
require BASE_DIR . '/config/routes.php';

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (PDOException $e) {
    http_response_code(500);
    echo $config['app']['debug']
        ? '<h3>Lỗi CSDL</h3><pre>' . e($e->getMessage()) . '</pre><p>Kiểm tra config/config.php và đã import database/schema.sql + seed.sql chưa.</p>'
        : 'Hệ thống đang gặp sự cố, vui lòng thử lại sau.';
}
