<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\VideoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ===========================================
// ROTAS PÚBLICAS (não precisam de token)
// ===========================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ===========================================
// ROTAS DE RECUPERAÇÃO DE SENHA
// ===========================================
Route::post('/forgot-password', [ForgotPasswordController::class, 'forgot']);
Route::post('/validate-token', [ForgotPasswordController::class, 'validateToken']);
Route::post('/reset-password', [ForgotPasswordController::class, 'reset']);

//===========================
// VÍDEOS PÚBLICOS
//===========================
    Route::get('/videos', [VideoController::class, 'index']);
    Route::get('/videos/{id}', [VideoController::class, 'show']);

// ===========================================
// ROTAS PROTEGIDAS (precisam de token)
// ===========================================
Route::middleware('auth:sanctum')->group(function () {

    // Dados do usuário logado
    Route::get('/user', [AuthController::class, 'user']);
    
    // =======================================
    // ROTAS ADMINISTRATIVAS (precisam de token + admin)
    // =======================================
    Route::middleware('admin')->prefix('admin')->group(function () {
        
        // Usuários
        Route::get('/users', [AdminController::class, 'listUsers']);
        Route::get('/users/{id}', [AdminController::class, 'getUser']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
        Route::post('/users/{id}/make-admin', [AdminController::class, 'makeAdmin']);
        Route::get('/stats', [AdminController::class, 'stats']);
        
        // VÍDEOS (admin)
        Route::post('/videos', [VideoController::class, 'store']);
        Route::put('/videos/{id}', [VideoController::class, 'update']);
        Route::delete('/videos/{id}', [VideoController::class, 'destroy']);

         // 🔥 NOVA ROTA DE UPLOAD (AQUI!)
        Route::post('/upload', [VideoController::class, 'upload']);
    });
});