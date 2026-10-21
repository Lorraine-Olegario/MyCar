import { formatCurrency, formatDate, formatKm, formatMonthYear } from './format';

const decimal = (value, digits) =>
    Number(value).toLocaleString('pt-BR', { minimumFractionDigits: digits, maximumFractionDigits: digits });

/**
 * Formata uma célula de relatório conforme o tipo da coluna. Valores nulos viram "—"
 * (dado ausente), nunca zero inventado.
 */
export function formatCell(type, value) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    switch (type) {
        case 'currency':
            return formatCurrency(value);
        case 'currency_decimal':
            return formatCurrency(Math.round(value));
        case 'date':
            return formatDate(value);
        case 'month':
            return formatMonthYear(value);
        case 'year':
            return String(value);
        case 'number':
            return decimal(value, 0);
        case 'decimal2':
            return decimal(value, 2);
        case 'decimal3':
            return decimal(value, 3);
        case 'percent':
            return `${decimal(value, 1)}%`;
        case 'km':
            return formatKm(value);
        default:
            return String(value);
    }
}

export const isNumericType = (type) => !['text', 'date', 'month', 'year'].includes(type);
