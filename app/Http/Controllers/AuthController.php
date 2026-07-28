<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Login por sessão (cookie), que é o padrão do Laravel para apps web.
 * O Vue só conversa por JSON com estes três endpoints.
 */
class AuthController extends Controller
{
    /**
     * GET /me — quem está logado. Usado pelo Vue ao abrir a página.
     *
     * Devolvemos {"user": null} em vez do usuário na raiz: json(null) vira "{}",
     * que é truthy no JavaScript e faria o front achar que há alguém logado.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }

    /** POST /login */
    public function login(Request $request): JsonResponse
    {
        // Já logado? Devolve o usuário atual em vez de tentar autenticar de novo.
        if ($request->user()) {
            return response()->json($request->user());
        }

        $credenciais = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credenciais, $request->boolean('lembrar'))) {
            // Lança 422 no mesmo formato dos erros de validação.
            throw ValidationException::withMessages([
                'email' => 'Credenciais inválidas.',
            ]);
        }

        // Troca o ID da sessão para evitar session fixation. Isso também gera um
        // novo token CSRF — por isso devolvemos o token novo no header, senão o
        // Vue continuaria usando o antigo e levaria 419 na próxima requisição.
        $request->session()->regenerate();

        return response()->json(Auth::user())
            ->header('X-CSRF-TOKEN', csrf_token());
    }

    /** POST /logout */
    public function logout(Request $request): JsonResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['mensagem' => 'Sessão encerrada.'])
            ->header('X-CSRF-TOKEN', csrf_token());
    }
}
