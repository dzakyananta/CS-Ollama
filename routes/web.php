<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/ollama/status', [ChatController::class, 'status'])->name('ollama.status');
Route::get('/conversations/{conversation}/messages', [ChatController::class, 'messages'])->name('conversations.messages');
Route::post('/chat', [ChatController::class, 'store'])->middleware('throttle:10,1')->name('chat.store');
