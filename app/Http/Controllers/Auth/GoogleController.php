<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect(){ return Socialite::driver('google')->redirect(); }
    public function callback(){
        $g = Socialite::driver('google')->user();
        $user = User::firstOrCreate(
            ['email'=>$g->getEmail()],
            ['name'=>$g->getName(),'google_id'=>$g->getId(),'avatar'=>$g->getAvatar(),'password'=>bcrypt(str()->random(32)),'role'=>User::count()===1?'admin':'user']
        );
        Auth::login($user);
        return redirect()->route('dashboard');
    }
}
