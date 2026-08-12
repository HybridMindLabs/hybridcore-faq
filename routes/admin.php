<?php

use Hybridcore\Faq\Http\Controllers\Admin\FaqController;
use Illuminate\Support\Facades\Route;

// Loaded with web + auth + admin middleware and the /admin prefix by the core.
Route::middleware('perm:faq.view')->group(function (): void {
    Route::get('/faq', [FaqController::class, 'index'])->name('admin.faq.index');
});

Route::middleware('perm:faq.manage')->group(function (): void {
    Route::post('/faq', [FaqController::class, 'store'])->name('admin.faq.store');
    Route::put('/faq/{entry}', [FaqController::class, 'update'])->name('admin.faq.update');
    Route::delete('/faq/{entry}', [FaqController::class, 'destroy'])->name('admin.faq.destroy');
});
