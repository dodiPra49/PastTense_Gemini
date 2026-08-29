<?php

use App\Http\Controllers\QuizController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Past & Perfect Tense Quiz App
|--------------------------------------------------------------------------
*/

Route::get('/', [QuizController::class, 'index'])->name('quiz.index');
Route::post('/quiz/start', [QuizController::class, 'start'])->name('quiz.start');
Route::get('/quiz/{session}', [QuizController::class, 'play'])->name('quiz.play');
Route::post('/quiz/{session}/submit', [QuizController::class, 'submit'])->name('quiz.submit');
Route::get('/quiz/{session}/dashboard', [QuizController::class, 'dashboard'])->name('quiz.dashboard');
Route::post('/quiz/{session}/retake', [QuizController::class, 'retake'])->name('quiz.retake');
