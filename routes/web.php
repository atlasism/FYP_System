<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\PasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PortalController::class, 'home'])->name('home');
Route::get('/manuals/{role}', function (string $role) {
    $manuals = ['student' => 'student_user_manual.pdf', 'supervisor' => 'supervisor_user_manual.pdf', 'panel' => 'panel_user_manual.pdf'];
    abort_unless(isset($manuals[$role]), 404);
    $path = base_path('legacy/assets/manuals/'.$manuals[$role]);
    abort_unless(is_file($path), 404);

    return response()->file($path, ['Content-Type' => 'application/pdf']);
})->where('role', 'student|supervisor|panel')->name('manuals.show');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [PortalController::class, 'dashboard'])->name('dashboard');
    Route::middleware('role:Student,Admin')->group(function () {
        Route::get('/account/password', [PasswordController::class, 'edit'])->name('password.edit');
        Route::put('/account/password', [PasswordController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
    });
    Route::get('/documents/{document}/download', [PortalController::class, 'downloadDocument'])->whereNumber('document')->name('documents.download');

    Route::prefix('student')->name('student.')->middleware('role:Student')->group(function () {
        Route::get('/dashboard', [PortalController::class, 'studentDashboard'])->name('dashboard');
        Route::get('/profile', [\App\Http\Controllers\StudentProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [\App\Http\Controllers\StudentProfileController::class, 'update'])->name('profile.update');
        Route::get('/documents', [PortalController::class, 'studentDocuments'])->name('documents.index');
        Route::get('/project', [PortalController::class, 'studentProjectPage'])->name('projects.show');
        Route::get('/milestones', [PortalController::class, 'studentMilestones'])->name('milestones.index');
        Route::get('/deadlines', [PortalController::class, 'studentDeadlines'])->name('deadlines.index');
        Route::get('/groups', [PortalController::class, 'studentGroups'])->name('groups.index');
        Route::get('/archive', [PortalController::class, 'studentArchive'])->name('archive.index');
        Route::get('/projects/create', [PortalController::class, 'createProject'])->name('projects.create');
        Route::post('/projects', [PortalController::class, 'storeProject'])->name('projects.store');
        Route::post('/documents', [PortalController::class, 'uploadDocument'])->name('documents.store');
    });

    Route::prefix('supervisor')->name('supervisor.')->middleware('role:Supervisor')->group(function () {
        Route::get('/dashboard', [PortalController::class, 'supervisorDashboard'])->name('dashboard');
        Route::get('/profile', [\App\Http\Controllers\AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [\App\Http\Controllers\AdminProfileController::class, 'update'])->name('profile.update');
        Route::get('/projects', [PortalController::class, 'supervisorProjects'])->name('projects.index');
        Route::get('/students', [PortalController::class, 'supervisorStudents'])->name('students.index');
        Route::get('/students/{student}/milestones', [PortalController::class, 'supervisorStudentMilestones'])->whereNumber('student')->name('students.milestones');
        Route::patch('/students/{student}/milestones', [PortalController::class, 'updateSupervisorStudentMilestones'])->whereNumber('student')->name('students.milestones.update');
        Route::get('/archive', [PortalController::class, 'supervisorArchive'])->name('archive.index');
        Route::get('/documents', [PortalController::class, 'supervisorDocuments'])->name('documents.index');
        Route::get('/documents/{project}', [PortalController::class, 'supervisorProjectDocuments'])->whereNumber('project')->name('documents.project');
        Route::get('/documents/{project}/files/{document}', [PortalController::class, 'viewSupervisorDocument'])->whereNumber('project')->whereNumber('document')->name('documents.view');
        Route::get('/deadlines', [PortalController::class, 'supervisorDeadlines'])->name('deadlines.index');
        Route::patch('/deadlines/{deadline}', [PortalController::class, 'supervisorUpdateDeadline'])->whereNumber('deadline')->name('deadlines.update');
        Route::patch('/documents/{document}', [PortalController::class, 'reviewDocument'])->whereNumber('document')->name('documents.review');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:Admin')->group(function () {
        Route::get('/dashboard', [PortalController::class, 'adminDashboard'])->name('dashboard');
        Route::get('/profile', [\App\Http\Controllers\AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [\App\Http\Controllers\AdminProfileController::class, 'update'])->name('profile.update');
        Route::get('/users', [AdminController::class, 'users'])->name('users.index');
        Route::get('/users/search-students', [AdminController::class, 'searchStudents'])->name('users.search-students');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
        Route::post('/users/import-students', [AdminController::class, 'importStudents'])->name('users.import-students');
        Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->whereNumber('user')->name('users.update');
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->whereNumber('user')->name('users.delete');
        Route::get('/projects', [AdminController::class, 'projects'])->name('projects.index');
        Route::patch('/projects/{project}/supervisor', [AdminController::class, 'assignProjectSupervisor'])->whereNumber('project')->name('projects.supervisor');
        Route::patch('/projects/{project}', [AdminController::class, 'updateProject'])->whereNumber('project')->name('projects.update');
        Route::get('/panel-qr', [AdminController::class, 'panelQr'])->name('panel-qr.index');
        Route::post('/panel-qr', [AdminController::class, 'generatePanelQr'])->name('panel-qr.generate');
        Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
        Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    });

    Route::prefix('panel')->name('panel.')->middleware('role:Panel')->group(function () {
        Route::get('/dashboard', [PortalController::class, 'panelDashboard'])->name('dashboard');
        Route::get('/profile', [\App\Http\Controllers\AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [\App\Http\Controllers\AdminProfileController::class, 'update'])->name('profile.update');
    });
});
