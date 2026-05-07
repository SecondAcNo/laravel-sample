<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketAttachmentController;
use App\Http\Controllers\TicketStatusController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\TicketAssignmentController as AdminTicketAssignmentController;
use App\Http\Controllers\Admin\TicketStatusController as AdminTicketStatusController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Agent\TicketController as AgentTicketController;
use App\Http\Controllers\Agent\TicketClaimController as AgentTicketClaimController;
use App\Http\Controllers\Agent\TicketStatusController as AgentTicketStatusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    $user = $request->user();

    if (! $user) {
        return redirect()->route('login');
    }

    if ($user->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    if ($user->isSupportAgent()) {
        return redirect()->route('agent.dashboard');
    }

    return redirect()->route('dashboard');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('tickets', TicketController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store'])
        ->name('tickets.comments.store');
    Route::post('/tickets/{ticket}/attachments', [TicketAttachmentController::class, 'store'])
        ->name('tickets.attachments.store');
    Route::get('/ticket-attachments/{attachment}/download', [TicketAttachmentController::class, 'download'])
        ->name('ticket-attachments.download');
    Route::patch('/tickets/{ticket}/close', [TicketStatusController::class, 'close'])
        ->name('tickets.close');

    Route::get('/agent/dashboard', [AgentTicketController::class, 'dashboard'])
        ->name('agent.dashboard');
    Route::get('/agent/tickets', [AgentTicketController::class, 'index'])
        ->name('agent.tickets.index');
    Route::get('/agent/tickets/{ticket}', [AgentTicketController::class, 'show'])
        ->name('agent.tickets.show');
    Route::patch('/agent/tickets/{ticket}/claim', [AgentTicketClaimController::class, 'update'])
        ->name('agent.tickets.claim.update');
    Route::patch('/agent/tickets/{ticket}/status', [AgentTicketStatusController::class, 'update'])
        ->name('agent.tickets.status.update');

    Route::get('/admin/dashboard', AdminDashboardController::class)
        ->name('admin.dashboard');
    Route::get('/admin/tickets', [AdminTicketController::class, 'index'])
        ->name('admin.tickets.index');
    Route::get('/admin/tickets/{ticket}', [AdminTicketController::class, 'show'])
        ->name('admin.tickets.show');
    Route::patch('/admin/tickets/{ticket}/assignee', [AdminTicketAssignmentController::class, 'update'])
        ->name('admin.tickets.assignee.update');
    Route::patch('/admin/tickets/{ticket}/status', [AdminTicketStatusController::class, 'update'])
        ->name('admin.tickets.status.update');
    Route::get('/admin/users', [AdminUserController::class, 'index'])
        ->name('admin.users.index');
    Route::get('/admin/users/create', [AdminUserController::class, 'create'])
        ->name('admin.users.create');
    Route::post('/admin/users', [AdminUserController::class, 'store'])
        ->name('admin.users.store');
    Route::get('/admin/users/{user}/edit', [AdminUserController::class, 'edit'])
        ->name('admin.users.edit');
    Route::patch('/admin/users/{user}', [AdminUserController::class, 'update'])
        ->name('admin.users.update');
    Route::get('/admin/categories', [AdminCategoryController::class, 'index'])
        ->name('admin.categories.index');
    Route::get('/admin/categories/create', [AdminCategoryController::class, 'create'])
        ->name('admin.categories.create');
    Route::post('/admin/categories', [AdminCategoryController::class, 'store'])
        ->name('admin.categories.store');
    Route::get('/admin/categories/{category}/edit', [AdminCategoryController::class, 'edit'])
        ->name('admin.categories.edit');
    Route::patch('/admin/categories/{category}', [AdminCategoryController::class, 'update'])
        ->name('admin.categories.update');
    Route::get('/admin/audit-logs', [AdminAuditLogController::class, 'index'])
        ->name('admin.audit-logs.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
