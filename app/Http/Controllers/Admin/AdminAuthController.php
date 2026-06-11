<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check() && in_array(Auth::user()->role, ['admin', 'super_admin', 'org_admin'], true)) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return back()
                ->withErrors(['email' => 'Неверный email или пароль'])
                ->withInput();
        }

        if (!in_array($user->role, ['admin', 'super_admin', 'org_admin'], true)) {
            return back()
                ->withErrors(['email' => 'Доступ разрешён только администратору'])
                ->withInput();
        }

        if ($user->banned_at) {
            return back()
                ->withErrors(['email' => 'Аккаунт заблокирован'])
                ->withInput();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}