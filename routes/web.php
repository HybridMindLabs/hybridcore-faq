<?php

use Hybridcore\Faq\Http\Controllers\Web\FaqController;
use Illuminate\Support\Facades\Route;

Route::get('/faq', [FaqController::class, 'index'])->name('faq.index');

// Throttled — a low-stakes counter, but unthrottled it's a trivial script
// target for skewing which answers look "helpful".
Route::post('/faq/{entry}/feedback', [FaqController::class, 'feedback'])
    ->middleware('throttle:20,1')
    ->name('faq.feedback');

Route::post('/faq/search-miss', [FaqController::class, 'searchMiss'])
    ->middleware('throttle:15,1')
    ->name('faq.search-miss');
