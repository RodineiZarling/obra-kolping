<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccessControlController;
use App\Http\Controllers\Api\PaymentsController;

Route::prefix('integracao/controle-acesso')
    ->middleware(['bridge.auth'])
    ->group(function () {
        Route::get('health', [AccessControlController::class, 'health']);
        Route::post('validar', [AccessControlController::class, 'validar']);
        Route::post('evento', [AccessControlController::class, 'evento']);
    });

// Pagamentos por parcela (para uso no painel / integrações internas)
Route::prefix('payments')->group(function () {
    Route::post('parcelas/{parcela}/pix', [PaymentsController::class, 'createPix']);
    Route::post('parcelas/{parcela}/boleto', [PaymentsController::class, 'createBoleto']);
});
