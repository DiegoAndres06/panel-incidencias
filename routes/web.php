<?php

use App\Http\Controllers\IncidentAssigneeController;
use App\Http\Controllers\IncidentCommentController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\IncidentStatusController;
use Illuminate\Support\Facades\Route;

Route::get('/', [IncidentController::class, 'index'])->name('home');

Route::get('incidents', [IncidentController::class, 'index'])->name('incidents.index');
Route::post('incidents', [IncidentController::class, 'store'])->name('incidents.store');
Route::get('incidents/{incident}', [IncidentController::class, 'show'])->name('incidents.show');
Route::patch('incidents/{incident}/status', [IncidentStatusController::class, 'update'])->name('incidents.status.update');
Route::patch('incidents/{incident}/assignee', [IncidentAssigneeController::class, 'update'])->name('incidents.assignee.update');
Route::post('incidents/{incident}/comments', [IncidentCommentController::class, 'store'])->name('incidents.comments.store');
