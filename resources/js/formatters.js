/** Só dígitos. */
export const digitos = (valor) => String(valor ?? '').replace(/\D/g, '');

/** 12345678901 -> 123.456.789-01 | 11222333000181 -> 11.222.333/0001-81 */
export function formatarDocumento(valor) {
    const d = digitos(valor);

    if (d.length <= 11) {
        return d
            .slice(0, 11)
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }

    return d
        .slice(0, 14)
        .replace(/(\d{2})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1/$2')
        .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
}

/** 11988887777 -> (11) 98888-7777 | 1133334444 -> (11) 3333-4444 */
export function formatarTelefone(valor) {
    const d = digitos(valor).slice(0, 11);

    if (d.length <= 10) {
        return d.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{4})(\d{1,4})$/, '$1-$2');
    }

    return d.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{5})(\d{1,4})$/, '$1-$2');
}
