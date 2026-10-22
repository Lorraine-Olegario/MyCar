import { describe, expect, it } from 'vitest';
import { formatCell, isNumericType } from './reportFormat';

const plain = (text) => text.replace(/\s/g, ' ');

describe('reportFormat', () => {
    it('formata moeda, datas e meses', () => {
        expect(plain(formatCell('currency', 123456))).toBe('R$ 1.234,56');
        expect(formatCell('date', '2024-02-29')).toBe('29/02/2024');
        expect(formatCell('month', '2026-03')).toBe('março de 2026');
        expect(formatCell('year', 2026)).toBe('2026');
    });

    it('formata números, percentuais e quilometragem', () => {
        expect(formatCell('number', 1234)).toBe('1.234');
        expect(formatCell('decimal2', 12.5)).toBe('12,50');
        expect(formatCell('decimal3', 40)).toBe('40,000');
        expect(plain(formatCell('price', 5.899))).toBe('R$ 5,899');
        expect(formatCell('percent', 33.333)).toBe('33,3%');
        expect(plain(formatCell('km', 52000))).toBe('52.000 km');
        expect(plain(formatCell('currency_decimal', 18.4))).toBe('R$ 0,18');
    });

    it('mostra traço para dado ausente em vez de zero', () => {
        expect(formatCell('currency', null)).toBe('—');
        expect(formatCell('decimal2', undefined)).toBe('—');
        expect(formatCell('currency', 0)).toBe('R$ 0,00'.replace(' ', ' '));
    });

    it('identifica colunas numéricas', () => {
        expect(isNumericType('currency')).toBe(true);
        expect(isNumericType('text')).toBe(false);
        expect(isNumericType('date')).toBe(false);
    });
});
