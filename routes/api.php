<?php

use App\Http\Controllers\PessoaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/api', function (Request $request) {
    return response()->json(['message' => 'Hello World!'], 200);
});

Route::get('/pessoas/{id}', [PessoaController::class, 'show']);
Route::get('/pessoas', [PessoaController::class, 'index']);
Route::post('/pessoas', [PessoaController::class, 'store']);

Route::get('/contagem-pessoas', [PessoaController::class, 'contagem']);
