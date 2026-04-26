<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\DocumentViewController;
use App\Http\Controllers\LocacaoCobrancaFaturaPdfController;
use App\Http\Controllers\ParcelaController;
use App\Http\Controllers\Webhooks\MercadoPagoWebhookController;

Route::get('/', fn() => redirect('/admin'));

Route::middleware(['auth'])->group(function () {
    Route::get('/documents/{document}/download', DocumentDownloadController::class)
        ->name('documents.download');

    Route::get('/documents/{document}/view', DocumentViewController::class)
        ->name('documents.view');

    Route::get('/locacao-cobrancas/{locacaoCobranca}/fatura.pdf', LocacaoCobrancaFaturaPdfController::class)
        ->name('locacao-cobrancas.fatura-pdf');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Webhook público do Mercado Pago
Route::post('/webhooks/mercadopago', MercadoPagoWebhookController::class);

// Route for marking a parcela as paid
Route::post('/pagar-parcela', [ParcelaController::class, 'pagarParcela'])
    ->middleware(['auth'])
    ->name('pagar.parcela');

// Standard logout endpoint (POST), mirroring header behavior
Route::post('/logout', function (Request $request) {
    try {
        Auth::guard()->logout();
    } catch (\Throwable $e) {
        // ignore if already logged out
    }

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/client');
})->name('logout');

// Legacy fallback: Logout current user via GET and redirect to the client area
Route::get('/logout-and-login', function (Request $request) {
    try {
        Auth::guard()->logout();
    } catch (\Throwable $e) {
        // ignore if already logged out
    }

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/client');
})->name('logout.and.login');

require __DIR__.'/auth.php';
