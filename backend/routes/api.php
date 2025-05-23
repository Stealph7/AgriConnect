<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\SmsAlertController;
use App\Http\Controllers\DroneDataController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\NotificationController;

// Routes publiques
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);

// Routes protégées par authentification
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [UserController::class, 'profile']);
    Route::put('user', [UserController::class, 'update']);

    // Produits
    Route::apiResource('products', ProductController::class);

    // Messages
    Route::get('messages/conversations', [MessageController::class, 'conversations']);
    Route::get('messages/conversation/{userId}', [MessageController::class, 'conversation']);
    Route::post('messages', [MessageController::class, 'send']);
    Route::put('messages/{id}/read', [MessageController::class, 'markAsRead']);
    Route::delete('messages/{id}', [MessageController::class, 'delete']);

    // Alertes SMS
    Route::apiResource('sms-alerts', SmsAlertController::class);

    // Données drones
    Route::apiResource('drone-data', DroneDataController::class);

    // Transactions
    Route::apiResource('transactions', TransactionController::class);
    Route::post('transactions/{id}/complete', [TransactionController::class, 'complete']);
    Route::post('transactions/{id}/cancel', [TransactionController::class, 'cancel']);

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::put('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
});
