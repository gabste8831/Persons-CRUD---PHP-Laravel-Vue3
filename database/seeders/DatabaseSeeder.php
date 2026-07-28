<?php

namespace Database\Seeders;

use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Usuário para entrar no sistema: admin@exemplo.com / senha123
        User::updateOrCreate(
            ['email' => 'admin@exemplo.com'],
            ['name' => 'Administrador', 'password' => Hash::make('senha123')],
        );

        // Alguns registros de exemplo (CPFs/CNPJ válidos para o validador aceitar).
        collect([
            ['nome' => 'Maria Souza', 'cpf' => '52998224725', 'tipo' => 'fisica', 'telefone' => '11988887777', 'email' => 'maria@exemplo.com'],
            ['nome' => 'João Lima', 'cpf' => '11144477735', 'tipo' => 'fisica', 'telefone' => '2133334444', 'email' => 'joao@exemplo.com'],
            ['nome' => 'Acme Comércio LTDA', 'cpf' => '11222333000181', 'tipo' => 'juridica', 'telefone' => '1130304040', 'email' => 'contato@acme.com'],
        ])->each(fn (array $dados) => Pessoa::updateOrCreate(['cpf' => $dados['cpf']], $dados));
    }
}
