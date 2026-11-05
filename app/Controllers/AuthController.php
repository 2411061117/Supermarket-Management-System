<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/');
        }
        $this->view('auth/login', ['title' => 'Đăng nhập'], 'layouts/blank');
    }

    public function login(): void
    {
        $username = (string) $this->input('username', '');
        $password = (string) $this->input('password', '');

        if ($username === '' || $password === '') {
            Session::flash('danger', 'Vui lòng nhập tên đăng nhập và mật khẩu.');
            redirect('/login');
        }

        if (!Auth::attempt($username, $password)) {
            // Không tiết lộ là sai username hay sai mật khẩu
            Session::flash('danger', 'Tên đăng nhập hoặc mật khẩu không đúng.');
            redirect('/login');
        }

        Session::flash('success', 'Xin chào ' . Auth::user()['full_name'] . '!');
        redirect('/');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/login');
    }
}
