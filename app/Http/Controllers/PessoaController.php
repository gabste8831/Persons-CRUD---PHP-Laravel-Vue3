<?php

namespace App\Http\Controllers;

use App\Http\Requests\PessoaRequest;
use App\Models\Pessoa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller "resource": cada método corresponde a uma ação do CRUD.
 * Todos devolvem JSON, porque quem desenha a tela é o Vue.
 */
class PessoaController extends Controller
{
    /** GET /pessoas — listagem com busca e paginação. */
    public function index(Request $request): JsonResponse
    {
        $pessoas = Pessoa::query()
            ->when($request->string('busca')->trim()->value(), function ($query, string $busca) {
                $somenteDigitos = preg_replace('/\D/', '', $busca);

                $query->where(function ($q) use ($busca, $somenteDigitos) {
                    $q->where('nome', 'like', "%{$busca}%")
                        ->orWhere('email', 'like', "%{$busca}%");

                    // Só busca por documento se o termo tiver algum número,
                    // senão o LIKE '%%' casaria com todo mundo.
                    if ($somenteDigitos !== '') {
                        $q->orWhere('cpf', 'like', "%{$somenteDigitos}%");
                    }
                });
            })
            ->when(in_array($request->input('tipo'), ['fisica', 'juridica'], true),
                fn ($query) => $query->where('tipo', $request->input('tipo')))
            ->orderBy('nome')
            ->paginate(10)
            ->withQueryString();

        return response()->json($pessoas);
    }

    /** POST /pessoas — cadastro. */
    public function store(PessoaRequest $request): JsonResponse
    {
        $pessoa = Pessoa::create($request->validated());

        return response()->json($pessoa, 201);
    }

    /** GET /pessoas/{pessoa} — um registro (usado para preencher o form de edição). */
    public function show(Pessoa $pessoa): JsonResponse
    {
        return response()->json($pessoa);
    }

    /** PUT /pessoas/{pessoa} — alteração. */
    public function update(PessoaRequest $request, Pessoa $pessoa): JsonResponse
    {
        $pessoa->update($request->validated());

        return response()->json($pessoa);
    }

    /** DELETE /pessoas/{pessoa} — exclusão. */
    public function destroy(Pessoa $pessoa): JsonResponse
    {
        $pessoa->delete();

        return response()->json(['mensagem' => 'Pessoa excluída com sucesso.']);
    }
}
