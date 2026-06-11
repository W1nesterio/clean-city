<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\DirectoryController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RewardController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json([
        'message' => 'API работает',
    ]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/register-worker', [AuthController::class, 'registerWorker']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/cities', [CityController::class, 'index']);
Route::get('/news', [NewsController::class, 'index']);
Route::get('/rewards', [RewardController::class, 'index']);

Route::middleware('api.token')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/profile/password', [ProfileController::class, 'changePassword']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile', [ProfileController::class, 'update']);
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me/points', [RewardController::class, 'myPoints']);
    Route::post('/rewards/{reward}/claim', [RewardController::class, 'claim']);

    Route::post('/tickets', [TicketController::class, 'store']);
    Route::get('/tickets/my', [TicketController::class, 'myTickets']);
    Route::get('/tickets/{ticket}', [TicketController::class, 'show']);
    Route::post('/tickets/{ticket}/status', [TicketController::class, 'changeStatus']);
    Route::post('/tickets/{ticket}/photo-after', [TicketController::class, 'uploadAfterPhoto']);

    Route::get('/worker/tickets', [TicketController::class, 'workerTickets']);
    Route::get('/worker/available-tickets', [TicketController::class, 'availableTickets']);
    Route::get('/resident/available-tasks', [TicketController::class, 'residentAvailableTasks']);
    Route::post('/tickets/{ticket}/resident-accept', [TicketController::class, 'residentAccept']);
    Route::post('/tickets/{ticket}/claim-request', [TicketController::class, 'claimRequest']);

    Route::get('/organizations', [DirectoryController::class, 'organizations']);
    Route::get('/admin/workers', [DirectoryController::class, 'workers']);

    Route::get('/admin/tickets', [TicketController::class, 'adminTickets']);
    Route::post('/admin/tickets/{ticket}/assign', [TicketController::class, 'assign']);
});
