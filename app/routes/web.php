<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PathologyController as AdminPathologyController;
use App\Http\Controllers\Admin\PermissionPreviewController;
use App\Http\Controllers\Admin\WorkflowController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PathologyController;
use App\Http\Controllers\VoiceSearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PathologyController::class, 'index'])->name('pathologies.index');
Route::post('/ricerca/vocale', VoiceSearchController::class)->middleware('throttle:10,1')->name('pathologies.voice');
Route::get('/ricerca/suggerimenti', [PathologyController::class, 'suggestions'])->name('pathologies.suggestions');
Route::get('/patologie/{pathology}/qr.svg', [PathologyController::class, 'qr'])->name('pathologies.qr');
Route::get('/patologie/{pathology}', [PathologyController::class, 'show'])->name('pathologies.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/accesso', [LoginController::class, 'create'])->name('login');
    Route::post('/accesso', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/amministrazione', DashboardController::class)->name('admin.dashboard');
    Route::get('/amministrazione/schede', [AdminPathologyController::class, 'index'])->name('admin.pathologies.index');
    Route::get('/amministrazione/schede/{pathology}/flusso', [WorkflowController::class, 'show'])->name('admin.pathologies.workflow');
    Route::post('/amministrazione/schede/{pathology}/flusso', [WorkflowController::class, 'transition'])->name('admin.pathologies.transition');
    Route::middleware('permission:edit-drafts')->group(function (): void {
        Route::get('/amministrazione/schede/{pathology}/modifica', [AdminPathologyController::class, 'edit'])->name('admin.pathologies.edit');
        Route::put('/amministrazione/schede/{pathology}', [AdminPathologyController::class, 'update'])->name('admin.pathologies.update');
    });
    Route::get('/amministrazione/redazione', PermissionPreviewController::class)
        ->middleware('permission:edit-drafts')
        ->defaults('title', 'Redazione delle bozze')
        ->defaults('description', 'Area riservata alla preparazione e modifica delle schede in bozza.')
        ->name('admin.editor');
    Route::get('/amministrazione/validazione', PermissionPreviewController::class)
        ->middleware('permission:validate-content')
        ->defaults('title', 'Validazione dei contenuti')
        ->defaults('description', 'Area riservata al controllo e alla validazione delle schede.')
        ->name('admin.validator');
    Route::get('/amministrazione/autorizzazione', PermissionPreviewController::class)
        ->middleware('permission:authorize-content')
        ->defaults('title', 'Autorizzazione del Direttore UOC')
        ->defaults('description', 'Area riservata all’autorizzazione finale delle schede validate.')
        ->name('admin.director');
    Route::get('/amministrazione/utenti', PermissionPreviewController::class)
        ->middleware('permission:manage-users')
        ->defaults('title', 'Gestione degli utenti')
        ->defaults('description', 'Area riservata all’amministratore per la gestione degli account.')
        ->name('admin.users');
    Route::post('/uscita', [LoginController::class, 'destroy'])->name('logout');
});
