<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pessoa extends Model
{
    use HasFactory;

    /**
     * O Eloquent adivinharia "pessoas" a partir de "Pessoa"? Não: ele pluraliza
     * em inglês e geraria "pessoas" só por sorte. Declarar é mais seguro.
     */
    protected $table = 'pessoas';

    /**
     * Campos que podem ser preenchidos em massa (Pessoa::create($dados)).
     * Tudo que não estiver aqui é ignorado — proteção contra mass assignment.
     */
    protected $fillable = [
        'nome',
        'cpf',
        'tipo',
        'telefone',
        'email',
    ];

    /**
     * Guardamos só dígitos no banco; a formatação é responsabilidade da tela.
     */
    public function setCpfAttribute(?string $value): void
    {
        $this->attributes['cpf'] = preg_replace('/\D/', '', (string) $value);
    }

    public function setTelefoneAttribute(?string $value): void
    {
        $this->attributes['telefone'] = preg_replace('/\D/', '', (string) $value);
    }
}
