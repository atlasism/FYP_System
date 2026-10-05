<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PortalController::class, 'home'])->name('home');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [PortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/documents/{document}/download', [PortalController::class, 'downloadDocument'])->whereNumber('document')->name('documents.download');

    Route::prefix('student')->name('student.')->middleware('role:Student')->group(function () {
        Route::get('/dashboard', [PortalController::class, 'studentDashboard'])->name('dashboard');
        Route::get('/projects/create', [PortalController::class, 'createProject'])->name('projects.create');
        Route::post('/projects', [PortalController::class, 'storeProject'])->name('projects.store');
        Route::post('/documents', [PortalController::class, 'uploadDocument'])->name('documents.store');
    });

    Route::prefix('supervisor')->name('supervisor.')->middleware('role:Supervisor')->group(function () {
        Route::get('/dashboard', [PortalController::class, 'supervisorDashboard'])->name('dashboard');
        Route::patch('/documents/{document}', [PortalController::class, 'reviewDocument'])->whereNumber('document')->name('documents.review');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:Admin')->group(function () {
        Route::get('/dashboard', [PortalController::class, 'adminDashboard'])->name('dashboard');
        Route::get('/users', [AdminController::class, 'users'])->name('users.index');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
        Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->whereNumber('user')->name('users.update');
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->whereNumber('user')->name('users.delete');
        Route::get('/projects', [AdminController::class, 'projects'])->name('projects.index');
        Route::patch('/projects/{project}', [AdminController::class, 'updateProject'])->whereNumber('project')->name('projects.update');
        Route::get('/deadlines', [AdminController::class, 'deadlines'])->name('deadlines.index');
        Route::post('/deadlines', [AdminController::class, 'storeDeadline'])->name('deadlines.store');
        Route::patch('/deadlines/{deadline}', [AdminController::class, 'updateDeadline'])->whereNumber('deadline')->name('deadlines.update');
        Route::delete('/deadlines/{deadline}', [AdminController::class, 'deleteDeadline'])->whereNumber('deadline')->name('deadlines.delete');
        Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
        Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
        Route::put('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
    });

    Route::prefix('panel')->name('panel.')->middleware('role:Panel')->group(function () {
        Route::get('/dashboard', [PortalController::class, 'panelDashboard'])->name('dashboard');
    });
});
