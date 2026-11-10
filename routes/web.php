<?php
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\TwoFactorController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\OnChainController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\PaperTradingController;
use App\Http\Controllers\AlertController;

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

// Portfolio
Route::middleware('auth')->prefix('api')->group(function () {
    Route::get('/portfolio/summary', [PortfolioController::class, 'summary']);
    Route::post('/portfolio/transaction', [PortfolioController::class, 'store']);
    Route::get('/portfolio/history', [PortfolioController::class, 'history']);

    // Paper Trading
    Route::post('/paper/trade', [PaperTradingController::class, 'store']);
    Route::get('/paper/summary', [PaperTradingController::class, 'summary']);
    Route::get('/paper/trades', [PaperTradingController::class, 'index']);

    // Alerts
    Route::get('/alerts', [AlertController::class, 'index']);
    Route::post('/alerts', [AlertController::class, 'store']);
    Route::delete('/alerts/{id}', [AlertController::class, 'destroy']);
    Route::get('/alerts/notifications', [AlertController::class, 'notifications']);
    Route::patch('/alerts/notifications/{id}/read', [AlertController::class, 'markRead']);
});

// Inertia Pages for Fase 5
Route::get('/portfolio', fn() => inertia('Portfolio/Index', ['initialSummary' => null]))->name('portfolio');
Route::get('/portfolio/transactions', fn() => inertia('Portfolio/Transactions'))->name('portfolio.transactions');
Route::get('/paper-trading', fn() => inertia('PaperTrading/Index'))->name('paper.trading');
Route::get('/settings/alerts', fn() => inertia('Settings/Alerts'))->name('settings.alerts');

// News
Route::middleware('auth')->prefix('api')->group(function () {
    Route::get('/news', [NewsController::class, 'index']);
    Route::get('/news/high-impact', [NewsController::class, 'highImpact']);
    Route::post('/news/refresh', [NewsController::class, 'refresh']);
});

// Settings
Route::middleware('auth')->prefix('api')->group(function () {
    Route::get('/settings', [SettingsController::class, 'index']);
    Route::patch('/settings/profile', [SettingsController::class, 'updateProfile']);
    Route::patch('/settings/password', [SettingsController::class, 'updatePassword']);
    Route::patch('/settings/theme', [SettingsController::class, 'updateTheme']);
    Route::get('/settings/api-health', [SettingsController::class, 'apiHealth']);
    // Admin
    Route::get('/settings/users', [SettingsController::class, 'users']);
    Route::post('/settings/users', [SettingsController::class, 'createUser']);
    Route::delete('/settings/users/{id}', [SettingsController::class, 'deleteUser']);
});

// Inertia Pages Fase 6
Route::get('/news', fn() => inertia('News/Index'))->name('news');
Route::get('/settings', fn() => inertia('Settings/Index'))->name('settings');
