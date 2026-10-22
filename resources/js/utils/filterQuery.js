/**
 * Mantém só os filtros preenchidos para a query string (sem chaves vazias).
 */
export function cleanQuery(filters) {
    return Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value !== null && value !== undefined && String(value).trim() !== ''),
    );
}

/**
 * Opções de período aceitas pelo servidor (a resolução das datas é feita no backend).
 */
export const PERIOD_PRESETS = [
    { value: 'last_12_months', label: 'Últimos 12 meses' },
    { value: 'this_month', label: 'Este mês' },
    { value: 'this_year', label: 'Este ano' },
    { value: 'last_year', label: 'Ano anterior' },
    { value: 'all', label: 'Todo o histórico' },
    { value: 'custom', label: 'Personalizado' },
];
