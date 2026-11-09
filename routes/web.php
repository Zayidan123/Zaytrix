<?php
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\TwoFactorController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\OnChainController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/2fa/setup', [TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->name('2fa.verify');
    Route::get('/dashboard', [MarketController::class,'index'])->name('dashboard');
    Route::get('/market', [MarketController::class,'index'])->name('market');
    Route::get('/onchain', [OnChainController::class,'index'])->name('onchain');
});

Route::get('/', fn() => redirect()->route('dashboard'));

// AI Routes (Fase 4)
Route::middleware('auth')->group(function () {
    Route::get('/ai/chat', [\App\Http\Controllers\AiController::class, 'chat'])->name('ai.chat');
    Route::post('/ai/chat/stream', [\App\Http\Controllers\AiController::class, 'streamChat'])->name('ai.chat.stream');
    Route::get('/ai/signal', [\App\Http\Controllers\AiController::class, 'signalForm'])->name('ai.signal');
    Route::post('/ai/signal', [\App\Http\Controllers\AiController::class, 'generateSignal'])->name('ai.signal.generate');
    Route::get('/ai/compare', [\App\Http\Controllers\AiController::class, 'compareForm'])->name('ai.compare');
    Route::post('/ai/compare', [\App\Http\Controllers\AiController::class, 'comparePdfs'])->name('ai.compare.generate');
    Route::post('/ai/documents', [\App\Http\Controllers\AiController::class, 'uploadDocument'])->name('ai.documents.upload');
});
