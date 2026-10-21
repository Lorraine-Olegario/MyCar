import { describe, expect, it } from 'vitest';
import { optionLabel } from './options';

const options = [
    { value: 'active', label: 'Ativo' },
    { value: 'sold', label: 'Vendido' },
];

describe('optionLabel', () => {
    it('devolve o rótulo do valor informado', () => {
        expect(optionLabel(options, 'sold')).toBe('Vendido');
    });

    it('devolve string vazia quando o valor não está na lista', () => {
        expect(optionLabel(options, 'archived')).toBe('');
    });

    it('devolve string vazia para valores vazios ou listas inválidas', () => {
        expect(optionLabel(options, '')).toBe('');
        expect(optionLabel(options, null)).toBe('');
        expect(optionLabel(options, undefined)).toBe('');
        expect(optionLabel(null, 'active')).toBe('');
        expect(optionLabel(undefined, 'active')).toBe('');
    });
});
