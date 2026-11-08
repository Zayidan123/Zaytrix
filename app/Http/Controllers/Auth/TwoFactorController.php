<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    protected Google2FA $g2fa;
    public function __construct(){ $this->g2fa = new Google2FA(); }

    public function setup(){
        $user = Auth::user();
        $secret = $this->g2fa->generateSecretKey();
        session(['2fa_secret'=>$secret]);
        $qr = $this->g2fa->getQRCodeUrl(config('app.name'), $user->email, $secret);
        return inertia('Auth/TwoFactorSetup', ['secret'=>$secret,'qr'=>$qr]);
    }

    public function verify(Request $r){
        $r->validate(['code'=>'required|digits:6']);
        $secret = session('2fa_secret');
        if(!$this->g2fa->verifyKey($secret, $r->code)){
            return back()->withErrors(['code'=>'Kode tidak valid.']);
        }
        $user = Auth::user();
        $user->totpSecret()->updateOrCreate(['user_id'=>$user->id],['secret'=>encrypt($secret),'enabled'=>true]);
        session()->forget('2fa_secret');
        return redirect()->route('dashboard')->with('success','2FA aktif.');
    }
}
