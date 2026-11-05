<?php
namespace App\Core;

/** Giữ cấu hình và base URL của ứng dụng. */
class App
{
    private static array $config = [];
    private static string $basePath = '';

    public static function boot(array $config): void
    {
        self::$config = $config;
        date_default_timezone_set($config['app']['timezone']);

        // VD: http://localhost/BTHT6/public/index.php -> base path "/BTHT6/public"
        self::$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    }

    public static function config(string $key): mixed
    {
        $value = self::$config;
        foreach (explode('.', $key) as $part) {
            $value = $value[$part] ?? null;
        }
        return $value;
    }

    public static function basePath(): string
    {
        return self::$basePath;
    }
}
