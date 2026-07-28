/**
 * Camada única de acesso HTTP. Concentra aqui o CSRF token e o tratamento
 * de erro para nenhum componente precisar se preocupar com isso.
 */
let csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

export class ApiError extends Error {
    constructor(message, status, errors = {}) {
        super(message);
        this.status = status;
        this.errors = errors; // { campo: ['mensagem', ...] } vindo da validação do Laravel
    }
}

async function request(method, url, body) {
    const response = await fetch(url, {
        method,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: body === undefined ? undefined : JSON.stringify(body),
    });

    // Login e logout rotacionam o token CSRF no servidor; ele volta neste header
    // para mantermos o cliente em dia sem precisar recarregar a página.
    const tokenNovo = response.headers.get('X-CSRF-TOKEN');

    if (tokenNovo) {
        csrfToken = tokenNovo;
        document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', tokenNovo);
    }

    const ehJson = (response.headers.get('content-type') ?? '').includes('application/json');
    const data = ehJson ? await response.json().catch(() => null) : null;

    // Resposta 200 mas em HTML significa que caímos em um redirect (sessão
    // expirada, por exemplo). Melhor falhar alto do que devolver null silencioso.
    if (response.ok && !ehJson) {
        throw new ApiError(
            'O servidor respondeu em HTML, não em JSON. Recarregue a página e tente novamente.',
            response.status,
        );
    }

    if (!response.ok) {
        if (response.status === 401) {
            // Avisa o App.vue para devolver o usuário à tela de login.
            window.dispatchEvent(new CustomEvent('sessao-expirada'));

            throw new ApiError('Sua sessão expirou. Entre novamente.', 401);
        }

        if (response.status === 419) {
            throw new ApiError('Sua sessão expirou. Recarregue a página.', 419);
        }

        throw new ApiError(
            data?.message ?? 'Não foi possível completar a operação.',
            response.status,
            data?.errors ?? {},
        );
    }

    return data;
}

export const api = {
    get: (url) => request('GET', url),
    post: (url, body) => request('POST', url, body ?? {}),
    put: (url, body) => request('PUT', url, body ?? {}),
    delete: (url) => request('DELETE', url),
};
