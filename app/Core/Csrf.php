<?php
namespace App\Core;

/** Chống CSRF: mọi form POST phải gửi kèm <?= csrf_field() ?> */
class Csrf
{
    public static function token(): string
    {
        if (!Session::get('_csrf')) {
            Session::set('_csrf', bin2hex(random_bytes(32)));
        }
        return Session::get('_csrf');
    }

    public static function verify(): void
    {
        $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($sent) || !hash_equals(self::token(), $sent)) {
            View::abort(419);
        }
    }
}
