<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PessoaController;
use Illuminate\Support\Facades\Route;

/*
 * Uma única rota de página: ela devolve o HTML que monta o Vue.
 * Todo o resto são endpoints JSON consumidos por fetch().
 */
// O nome "login" é usado pelo middleware "auth" para redirecionar visitantes.
Route::view('/', 'app')->name('login');

// Sem o middleware "guest" de propósito: ele responderia com um redirect 302
// para quem já tem sessão, e o fetch() do Vue não sabe o que fazer com HTML.
// O próprio controller trata o caso de já estar logado.
Route::post('/login', [AuthController::class, 'login']);
Route::get('/me', [AuthController::class, 'me']);

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Gera as 5 rotas do CRUD de uma vez (index, store, show, update, destroy).
    Route::apiResource('pessoas', PessoaController::class);
});
