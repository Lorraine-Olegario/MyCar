import { describe, expect, it } from 'vitest';
import { cleanQuery, PERIOD_PRESETS } from './filterQuery';

describe('filterQuery', () => {
    it('remove filtros vazios e preserva os preenchidos', () => {
        expect(cleanQuery({ vehicle_id: '3', category_id: '', from: null, to: undefined, period: 'custom', q: '  ' })).toEqual({
            vehicle_id: '3',
            period: 'custom',
        });
    });

    it('mantém valores numéricos, inclusive zero', () => {
        expect(cleanQuery({ page: 0, year: 2024 })).toEqual({ page: 0, year: 2024 });
    });

    it('expõe os períodos que o servidor entende', () => {
        expect(PERIOD_PRESETS.map((preset) => preset.value)).toEqual([
            'last_12_months', 'this_month', 'this_year', 'last_year', 'all', 'custom',
        ]);
    });
});
