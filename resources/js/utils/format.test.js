import { describe, expect, it } from 'vitest';
import {
    formatCurrency,
    formatDate,
    formatKm,
    formatMonthYear,
    parseCurrencyInput,
    parseDateInput,
} from './format';

describe('formatCurrency', () => {
    it('formata centavos como moeda brasileira', () => {
        expect(formatCurrency(123456)).toBe('R$ 1.234,56');
        expect(formatCurrency(0)).toBe('R$ 0,00');
        expect(formatCurrency(5)).toBe('R$ 0,05');
    });

    it('aceita valores negativos', () => {
        expect(formatCurrency(-123456)).toBe('-R$ 1.234,56');
    });

    it('devolve string vazia para valores inválidos', () => {
        expect(formatCurrency('abc')).toBe('');
    });
});

describe('formatDate', () => {
    it('formata strings YYYY-MM-DD sem deslocamento de fuso', () => {
        expect(formatDate('2026-10-21')).toBe('21/10/2026');
        expect(formatDate('2026-01-01')).toBe('01/01/2026');
    });

    it('ignora a parte de hora em strings ISO completas', () => {
        expect(formatDate('2026-10-21T00:00:00.000000Z')).toBe('21/10/2026');
    });

    it('formata objetos Date usando o horário local', () => {
        expect(formatDate(new Date(2026, 9, 21))).toBe('21/10/2026');
    });

    it('devolve string vazia para entradas vazias ou inválidas', () => {
        expect(formatDate(null)).toBe('');
        expect(formatDate('')).toBe('');
        expect(formatDate('nao-e-data')).toBe('');
    });
});

describe('formatMonthYear', () => {
    it('formata YYYY-MM por extenso', () => {
        expect(formatMonthYear('2026-10')).toBe('outubro de 2026');
    });

    it('aceita YYYY-MM-DD sem deslocamento de fuso', () => {
        expect(formatMonthYear('2026-01-15')).toBe('janeiro de 2026');
    });
});

describe('formatKm', () => {
    it('formata a quilometragem com separador de milhar', () => {
        expect(formatKm(12345)).toBe('12.345 km');
        expect(formatKm(0)).toBe('0 km');
    });

    it('devolve string vazia para valores inválidos', () => {
        expect(formatKm('abc')).toBe('');
    });
});

describe('parseCurrencyInput', () => {
    it('converte texto de moeda em centavos', () => {
        expect(parseCurrencyInput('R$ 1.234,56')).toBe(123456);
        expect(parseCurrencyInput('1234,56')).toBe(123456);
        expect(parseCurrencyInput('1.234,56')).toBe(123456);
        expect(parseCurrencyInput('1234.56')).toBe(123456);
    });

    it('trata valores negativos', () => {
        expect(parseCurrencyInput('-R$ 10,00')).toBe(-1000);
    });

    it('devolve 0 para entradas vazias ou inválidas', () => {
        expect(parseCurrencyInput('')).toBe(0);
        expect(parseCurrencyInput('abc')).toBe(0);
    });
});

describe('parseDateInput', () => {
    it('converte dd/mm/aaaa para o formato do banco', () => {
        expect(parseDateInput('21/10/2026')).toBe('2026-10-21');
        expect(parseDateInput('01/01/2026')).toBe('2026-01-01');
    });

    it('devolve string vazia para entradas incompletas', () => {
        expect(parseDateInput('21/10')).toBe('');
        expect(parseDateInput('2')).toBe('');
        expect(parseDateInput('')).toBe('');
        expect(parseDateInput(null)).toBe('');
    });

    it('rejeita datas inexistentes', () => {
        expect(parseDateInput('31/02/2026')).toBe('');
        expect(parseDateInput('00/10/2026')).toBe('');
        expect(parseDateInput('21/13/2026')).toBe('');
    });
});
