<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../api';
import Login from './Login.vue';
import PessoaForm from './PessoaForm.vue';
import PessoaList from './PessoaList.vue';

const user = ref(null);
const carregandoSessao = ref(true);
const tela = ref('lista'); // 'lista' | 'form'
const pessoaEmEdicao = ref(null);
const aviso = ref('');
const lista = ref(null);

onMounted(async () => {
    // Ao abrir a página perguntamos ao servidor se já existe sessão ativa.
    try {
        const resposta = await api.get('/me');
        user.value = resposta?.user ?? null;
    } catch {
        user.value = null;
    } finally {
        carregandoSessao.value = false;
    }
});

// Se qualquer requisição levar 401 (sessão expirou no meio do uso), voltamos
// para a tela de login em vez de deixar a tela travada com erro.
window.addEventListener('sessao-expirada', () => {
    user.value = null;
    tela.value = 'lista';
});

async function sair() {
    await api.post('/logout');
    user.value = null;
    tela.value = 'lista';
}

function abrirNovo() {
    pessoaEmEdicao.value = null;
    tela.value = 'form';
}

function abrirEdicao(pessoa) {
    pessoaEmEdicao.value = pessoa;
    tela.value = 'form';
}

function aoSalvar() {
    aviso.value = pessoaEmEdicao.value ? 'Pessoa alterada com sucesso.' : 'Pessoa cadastrada com sucesso.';
    tela.value = 'lista';
    // Espera a lista remontar para então recarregar os dados.
    setTimeout(() => lista.value?.carregar(1), 0);
    setTimeout(() => (aviso.value = ''), 4000);
}
</script>

<template>
    <div v-if="carregandoSessao" class="flex min-h-screen items-center justify-center text-red-500">
        Carregando…
    </div>

    <Login v-else-if="!user" @autenticado="user = $event" />

    <div v-else class="mx-auto max-w-6xl px-4 py-8">
        <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Pessoas</h1>
                <p class="text-sm text-gray-500">Conectado como {{ user.name }}</p>
            </div>
            <button
                class="rounded-md border border-gray-300 px-4 py-2 transition hover:bg-gray-100"
                @click="sair"
            >
                Sair
            </button>
        </header>

        <p v-if="aviso" class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ aviso }}
        </p>

        <PessoaList
            v-if="tela === 'lista'"
            ref="lista"
            @novo="abrirNovo"
            @editar="abrirEdicao"
        />

        <PessoaForm
            v-else
            :pessoa="pessoaEmEdicao"
            @salvo="aoSalvar"
            @cancelar="tela = 'lista'"
        />
    </div>
</template>
