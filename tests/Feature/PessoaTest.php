<?php

namespace Tests\Feature;

use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PessoaTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): User
    {
        return User::factory()->create();
    }

    public function test_visitante_nao_acessa_a_listagem(): void
    {
        $this->getJson('/pessoas')->assertUnauthorized();
    }

    public function test_login_com_credenciais_validas(): void
    {
        $user = User::factory()->create(['password' => bcrypt('senha123')]);

        $this->postJson('/login', ['email' => $user->email, 'password' => 'senha123'])
            ->assertOk()
            ->assertJsonPath('email', $user->email);

        $this->assertAuthenticatedAs($user);
    }

    public function test_me_devolve_user_nulo_para_visitante(): void
    {
        // Precisa ser {"user":null}: json(null) viraria "{}", que é truthy no
        // JavaScript e faria o front pular a tela de login.
        $this->getJson('/me')
            ->assertOk()
            ->assertExactJson(['user' => null]);
    }

    public function test_me_devolve_o_usuario_logado(): void
    {
        $user = $this->usuario();

        $this->actingAs($user)
            ->getJson('/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_login_devolve_o_novo_token_csrf_no_header(): void
    {
        $user = User::factory()->create(['password' => bcrypt('senha123')]);

        // Sem esse header o front continuaria com o token antigo (rotacionado
        // pelo session()->regenerate()) e tomaria 419 na próxima requisição.
        $this->postJson('/login', ['email' => $user->email, 'password' => 'senha123'])
            ->assertOk()
            ->assertHeader('X-CSRF-TOKEN', csrf_token());
    }

    public function test_login_repetido_com_sessao_ativa_devolve_json(): void
    {
        $user = User::factory()->create(['password' => bcrypt('senha123')]);

        $this->actingAs($user)
            ->postJson('/login', ['email' => $user->email, 'password' => 'senha123'])
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    }

    public function test_login_com_senha_errada_falha(): void
    {
        $user = User::factory()->create();

        $this->postJson('/login', ['email' => $user->email, 'password' => 'errada'])
            ->assertStatus(422);

        $this->assertGuest();
    }

    public function test_cadastra_pessoa_fisica(): void
    {
        $this->actingAs($this->usuario())
            ->postJson('/pessoas', [
                'nome' => 'Maria Souza',
                'cpf' => '529.982.247-25',
                'tipo' => 'fisica',
                'telefone' => '(11) 98888-7777',
                'email' => 'maria@exemplo.com',
            ])
            ->assertCreated();

        // Máscaras removidas antes de gravar.
        $this->assertDatabaseHas('pessoas', ['cpf' => '52998224725', 'telefone' => '11988887777']);
    }

    public function test_recusa_cpf_invalido(): void
    {
        $this->actingAs($this->usuario())
            ->postJson('/pessoas', [
                'nome' => 'Fulano',
                'cpf' => '111.111.111-11',
                'tipo' => 'fisica',
                'telefone' => '11988887777',
                'email' => 'fulano@exemplo.com',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('cpf');
    }

    public function test_cadastra_pessoa_juridica_com_cnpj(): void
    {
        $this->actingAs($this->usuario())
            ->postJson('/pessoas', [
                'nome' => 'Acme LTDA',
                'cpf' => '11.222.333/0001-81',
                'tipo' => 'juridica',
                'telefone' => '1130304040',
                'email' => 'contato@acme.com',
            ])
            ->assertCreated();
    }

    public function test_recusa_cpf_no_tipo_juridica(): void
    {
        $this->actingAs($this->usuario())
            ->postJson('/pessoas', [
                'nome' => 'Acme LTDA',
                'cpf' => '52998224725',
                'tipo' => 'juridica',
                'telefone' => '1130304040',
                'email' => 'contato@acme.com',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('cpf');
    }

    public function test_altera_pessoa_mantendo_o_proprio_cpf(): void
    {
        $pessoa = Pessoa::create([
            'nome' => 'Maria', 'cpf' => '52998224725', 'tipo' => 'fisica',
            'telefone' => '11988887777', 'email' => 'maria@exemplo.com',
        ]);

        $this->actingAs($this->usuario())
            ->putJson("/pessoas/{$pessoa->id}", [
                'nome' => 'Maria Souza Lima',
                'cpf' => '529.982.247-25',
                'tipo' => 'fisica',
                'telefone' => '11988887777',
                'email' => 'maria@exemplo.com',
            ])
            ->assertOk();

        $this->assertDatabaseHas('pessoas', ['id' => $pessoa->id, 'nome' => 'Maria Souza Lima']);
    }

    public function test_recusa_cpf_duplicado(): void
    {
        Pessoa::create([
            'nome' => 'Maria', 'cpf' => '52998224725', 'tipo' => 'fisica',
            'telefone' => '11988887777', 'email' => 'maria@exemplo.com',
        ]);

        $this->actingAs($this->usuario())
            ->postJson('/pessoas', [
                'nome' => 'Outra', 'cpf' => '52998224725', 'tipo' => 'fisica',
                'telefone' => '11977776666', 'email' => 'outra@exemplo.com',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('cpf');
    }

    public function test_exclui_pessoa(): void
    {
        $pessoa = Pessoa::create([
            'nome' => 'Maria', 'cpf' => '52998224725', 'tipo' => 'fisica',
            'telefone' => '11988887777', 'email' => 'maria@exemplo.com',
        ]);

        $this->actingAs($this->usuario())
            ->deleteJson("/pessoas/{$pessoa->id}")
            ->assertOk();

        $this->assertDatabaseMissing('pessoas', ['id' => $pessoa->id]);
    }

    public function test_busca_filtra_por_nome(): void
    {
        Pessoa::create(['nome' => 'Maria', 'cpf' => '52998224725', 'tipo' => 'fisica', 'telefone' => '11988887777', 'email' => 'maria@exemplo.com']);
        Pessoa::create(['nome' => 'João', 'cpf' => '11144477735', 'tipo' => 'fisica', 'telefone' => '2133334444', 'email' => 'joao@exemplo.com']);

        $this->actingAs($this->usuario())
            ->getJson('/pessoas?busca=Maria')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nome', 'Maria');
    }
}
