<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin(){ return inertia('Auth/Login'); }
    public function showRegister(){ return inertia('Auth/Register'); }

    public function register(Request $r){
        $r->validate([
            'name'=>'required|string|max:255',
            'email'=>'required|email|unique:users,email',
            'password'=>'required|min:8|confirmed',
        ]);
        $user = User::create([
            'name'=>$r->name,
            'email'=>$r->email,
            'password'=>Hash::make($r->password),
            'role'=> User::count()===0 ? 'admin' : 'user',
        ]);
        Auth::login($user);
        return redirect()->route('dashboard');
    }

    public function login(Request $r){
        $r->validate(['email'=>'required|email','password'=>'required']);
        if(!Auth::attempt($r->only('email','password'), $r->boolean('remember'))){
            return back()->withErrors(['email'=>'Kredensial tidak valid.']);
        }
        $r->session()->regenerate();
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $r){
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect()->route('login');
    }
}
