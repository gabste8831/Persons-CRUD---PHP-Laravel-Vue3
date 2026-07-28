<?php

namespace App\Http\Requests;

use App\Rules\CpfCnpj;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FormRequest = classe que valida a requisição ANTES de o controller rodar.
 * Se algo falhar, o Laravel devolve 422 com os erros — o controller nem executa.
 */
class PessoaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // a proteção de acesso já é feita pelo middleware "auth"
    }

    /**
     * Normaliza a entrada antes de validar: máscara do front vira só dígitos.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'cpf' => preg_replace('/\D/', '', (string) $this->input('cpf')),
            'telefone' => preg_replace('/\D/', '', (string) $this->input('telefone')),
        ]);
    }

    public function rules(): array
    {
        // Em update precisamos ignorar o próprio registro nas regras "unique".
        $id = $this->route('pessoa')?->id;

        return [
            'nome' => ['required', 'string', 'min:3', 'max:255'],
            'tipo' => ['required', Rule::in(['fisica', 'juridica'])],
            'cpf' => [
                'required',
                Rule::unique('pessoas', 'cpf')->ignore($id),
                new CpfCnpj($this->input('tipo')),
            ],
            'telefone' => ['required', 'digits_between:10,11'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('pessoas', 'email')->ignore($id),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'nome' => 'nome',
            'cpf' => 'CPF/CNPJ',
            'tipo' => 'tipo',
            'telefone' => 'telefone',
            'email' => 'e-mail',
        ];
    }
}
