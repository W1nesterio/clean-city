<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminComplaintController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminAnalyticsController;
use App\Http\Controllers\Admin\AdminEmployeeController;
use App\Http\Controllers\Admin\AdminTicketController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminOrganizationController;
use App\Http\Controllers\Admin\AdminClaimRequestController;
use App\Http\Controllers\Admin\WorkerRegistrationCodeController;
use App\Http\Controllers\Admin\AdminNewsController;
use App\Http\Controllers\Admin\AdminRewardController;
use App\Http\Controllers\Admin\AdminPointsController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing.mobile')->name('landing.mobile');

Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

Route::get('/admin/analytics', [AdminAnalyticsController::class, 'index'])->name('admin.analytics.index');
Route::get('/admin/analytics/export', [AdminAnalyticsController::class, 'export'])->name('admin.analytics.export');
Route::get('/admin/analytics/pdf', [AdminAnalyticsController::class, 'pdf'])->name('admin.analytics.pdf');

Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
Route::post('/admin/users/{user}/ban', [AdminUserController::class, 'ban'])->name('admin.users.ban');
Route::post('/admin/users/{user}/unban', [AdminUserController::class, 'unban'])->name('admin.users.unban');

Route::get('/admin/organizations', [AdminOrganizationController::class, 'index'])->name('admin.organizations.index');
Route::get('/admin/organizations/{organization}', [AdminOrganizationController::class, 'show'])->name('admin.organizations.show');

Route::get('/admin/employees', [AdminEmployeeController::class, 'index'])->name('admin.employees.index');
Route::post('/admin/employees/codes', [AdminEmployeeController::class, 'storeCode'])->name('admin.employees.codes.store');
Route::post('/admin/employees/codes/{workerCode}/deactivate', [AdminEmployeeController::class, 'deactivateCode'])->name('admin.employees.codes.deactivate');
Route::delete('/admin/employees/codes/{workerCode}', [AdminEmployeeController::class, 'deleteCode'])->name('admin.employees.codes.delete');

Route::get('/admin/worker-codes', [WorkerRegistrationCodeController::class, 'index'])->name('admin.worker-codes.index');
Route::post('/admin/worker-codes', [WorkerRegistrationCodeController::class, 'store'])->name('admin.worker-codes.store');
Route::post('/admin/worker-codes/{workerCode}/deactivate', [WorkerRegistrationCodeController::class, 'deactivate'])->name('admin.worker-codes.deactivate');


Route::get('/admin/claim-requests', [AdminClaimRequestController::class, 'index'])->name('admin.claim-requests.index');
Route::post('/admin/claim-requests/{claimRequest}/approve', [AdminClaimRequestController::class, 'approve'])->name('admin.claim-requests.approve');
Route::post('/admin/claim-requests/{claimRequest}/reject', [AdminClaimRequestController::class, 'reject'])->name('admin.claim-requests.reject');

// News
Route::get('/admin/news', [AdminNewsController::class, 'index'])->name('admin.news.index');
Route::get('/admin/news/create', [AdminNewsController::class, 'create'])->name('admin.news.create');
Route::post('/admin/news', [AdminNewsController::class, 'store'])->name('admin.news.store');
Route::get('/admin/news/{news}/edit', [AdminNewsController::class, 'edit'])->name('admin.news.edit');
Route::put('/admin/news/{news}', [AdminNewsController::class, 'update'])->name('admin.news.update');
Route::delete('/admin/news/{news}', [AdminNewsController::class, 'destroy'])->name('admin.news.destroy');

// Rewards / Coupons
Route::get('/admin/rewards', [AdminRewardController::class, 'index'])->name('admin.rewards.index');
Route::get('/admin/rewards/create', [AdminRewardController::class, 'create'])->name('admin.rewards.create');
Route::post('/admin/rewards', [AdminRewardController::class, 'store'])->name('admin.rewards.store');
Route::get('/admin/rewards/{reward}/edit', [AdminRewardController::class, 'edit'])->name('admin.rewards.edit');
Route::put('/admin/rewards/{reward}', [AdminRewardController::class, 'update'])->name('admin.rewards.update');
Route::delete('/admin/rewards/{reward}', [AdminRewardController::class, 'destroy'])->name('admin.rewards.destroy');

// Points management
Route::get('/admin/points', [AdminPointsController::class, 'index'])->name('admin.points.index');
Route::post('/admin/points/{user}/adjust', [AdminPointsController::class, 'adjust'])->name('admin.points.adjust');
Route::get('/admin/points/{user}/history', [AdminPointsController::class, 'history'])->name('admin.points.history');

Route::get('/admin/tickets', [AdminTicketController::class, 'index'])->name('admin.tickets.index');
Route::get('/admin/tickets/map', [AdminTicketController::class, 'map'])->name('admin.tickets.map');
Route::get('/admin/tickets/{ticket}', [AdminTicketController::class, 'show'])->name('admin.tickets.show');
Route::post('/admin/tickets/{ticket}/assign', [AdminTicketController::class, 'assign'])->name('admin.tickets.assign');
Route::post('/admin/tickets/{ticket}/status', [AdminTicketController::class, 'changeStatus'])->name('admin.tickets.status');
Route::post('/admin/tickets/{ticket}/delete', [AdminTicketController::class, 'softDelete'])->name('admin.tickets.delete');
Route::post('/admin/tickets/{ticket}/hide', [AdminTicketController::class, 'hide'])->name('admin.tickets.hide');
Route::post('/admin/tickets/{ticket}/restore', [AdminTicketController::class, 'restore'])->name('admin.tickets.restore');
Route::post('/admin/tickets/{ticket}/toggle-resident', [AdminTicketController::class, 'toggleResidentAvailability'])->name('admin.tickets.toggle-resident');

// Complaints
Route::get('/admin/complaints', [AdminComplaintController::class, 'index'])->name('admin.complaints.index');
Route::get('/admin/complaints/{complaint}', [AdminComplaintController::class, 'show'])->name('admin.complaints.show');
Route::post('/admin/complaints/{complaint}/resolve', [AdminComplaintController::class, 'resolve'])->name('admin.complaints.resolve');

// Cities
Route::get('/admin/cities', [\App\Http\Controllers\Admin\AdminCityController::class, 'index'])->name('admin.cities.index');
Route::post('/admin/cities', [\App\Http\Controllers\Admin\AdminCityController::class, 'store'])->name('admin.cities.store');
Route::delete('/admin/cities/{city}', [\App\Http\Controllers\Admin\AdminCityController::class, 'destroy'])->name('admin.cities.destroy');
