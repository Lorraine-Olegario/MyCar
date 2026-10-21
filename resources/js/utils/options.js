/**
 * Devolve o rótulo de um valor dentro de uma lista de opções `{ value, label }`
 * enviada pelo backend (ex.: status e categoria do veículo).
 */
export function optionLabel(options, value) {
    if (!Array.isArray(options) || value === null || value === undefined || value === '') {
        return '';
    }

    return options.find((option) => option?.value === value)?.label ?? '';
}
