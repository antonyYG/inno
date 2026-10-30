<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {

        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $ipKey = 'login:ip:' . $request->ip();

        if (RateLimiter::tooManyAttempts($ipKey, 3)) {

            $seconds=RateLimiter::availableIn($ipKey);
            return back()->withErrors([
                'email' => "Demasiados intentos. Intenta nuevamente en {$seconds} segundos."
            ])->onlyInput('email');
        }

        if (!Auth::attempt($data)) {
            RateLimiter::hit($ipKey, 300);

            return back()->withErrors([
                'email' => 'Las credenciales no son validas',
            ])->onlyInput('email');
        }
        RateLimiter::clear($ipKey);

        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

}
