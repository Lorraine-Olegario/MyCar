const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const integerFormatter = new Intl.NumberFormat('pt-BR', {
    maximumFractionDigits: 0,
});

const monthYearFormatter = new Intl.DateTimeFormat('pt-BR', {
    month: 'long',
    year: 'numeric',
});

const pad = (value) => String(value).padStart(2, '0');

const toLocalDate = (year, month, day) => new Date(Number(year), Number(month) - 1, Number(day));

/**
 * Formata um valor em centavos como moeda brasileira (ex.: 123456 -> "R$ 1.234,56").
 */
export function formatCurrency(cents) {
    const value = Number(cents ?? 0) / 100;

    if (!Number.isFinite(value)) {
        return '';
    }

    return currencyFormatter.format(value).replace(/\u00A0/g, ' ');
}

/**
 * Formata uma data como dd/mm/aaaa. Strings ISO (YYYY-MM-DD ou com hora) são
 * lidas literalmente para evitar deslocamento de fuso.
 */
export function formatDate(value) {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    if (typeof value === 'string') {
        const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value);

        if (match) {
            return `${match[3]}/${match[2]}/${match[1]}`;
        }
    }

    const date = value instanceof Date ? value : new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return `${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()}`;
}

/**
 * Formata mês e ano por extenso (ex.: "2026-10" -> "outubro de 2026").
 * Aceita "YYYY-MM" ou "YYYY-MM-DD" sem sofrer deslocamento de fuso.
 */
export function formatMonthYear(value) {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    if (typeof value === 'string') {
        const match = /^(\d{4})-(\d{2})/.exec(value);

        if (match) {
            return monthYearFormatter.format(toLocalDate(match[1], match[2], 1));
        }
    }

    const date = value instanceof Date ? value : new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return monthYearFormatter.format(toLocalDate(date.getFullYear(), date.getMonth() + 1, 1));
}

/**
 * Formata uma quilometragem inteira (ex.: 12345 -> "12.345 km").
 */
export function formatKm(value) {
    const number = Number(value);

    if (!Number.isFinite(number)) {
        return '';
    }

    return `${integerFormatter.format(number)} km`;
}

/**
 * Converte uma entrada de moeda digitada (ex.: "R$ 1.234,56") em centavos (123456).
 */
export function parseCurrencyInput(value) {
    if (value === null || value === undefined || value === '') {
        return 0;
    }

    let normalized = String(value).replace(/[^\d,.-]/g, '');
    const negative = normalized.trim().startsWith('-');

    normalized = normalized.replace(/-/g, '');

    if (normalized.includes(',')) {
        normalized = normalized.replace(/\./g, '').replace(',', '.');
    }

    const parsed = Number.parseFloat(normalized);

    if (Number.isNaN(parsed)) {
        return 0;
    }

    const cents = Math.round(parsed * 100);

    return negative ? -cents : cents;
}
