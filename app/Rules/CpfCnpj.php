<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Regra de validação customizada: valida CPF (pessoa física) ou CNPJ
 * (pessoa jurídica) conforme o "tipo" enviado no formulário.
 */
class CpfCnpj implements ValidationRule
{
    public function __construct(private ?string $tipo) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        $ok = $this->tipo === 'juridica'
            ? $this->cnpjValido($digits)
            : $this->cpfValido($digits);

        if (! $ok) {
            $fail($this->tipo === 'juridica'
                ? 'O CNPJ informado é inválido.'
                : 'O CPF informado é inválido.');
        }
    }

    private function cpfValido(string $cpf): bool
    {
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // Os dois últimos dígitos são verificadores calculados sobre os anteriores.
        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int) $cpf[$i] * (($t + 1) - $i);
            }
            $digito = ((10 * $soma) % 11) % 10;

            if ((int) $cpf[$t] !== $digito) {
                return false;
            }
        }

        return true;
    }

    private function cnpjValido(string $cnpj): bool
    {
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        foreach ([12, 13] as $posicao) {
            $peso = $posicao - 7;
            $soma = 0;

            for ($i = $posicao; $i > 0; $i--) {
                $soma += (int) $cnpj[$posicao - $i] * $peso--;

                if ($peso < 2) {
                    $peso = 9;
                }
            }

            $digito = $soma % 11 < 2 ? 0 : 11 - ($soma % 11);

            if ((int) $cnpj[$posicao] !== $digito) {
                return false;
            }
        }

        return true;
    }
}
