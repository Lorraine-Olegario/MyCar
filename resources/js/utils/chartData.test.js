import { describe, expect, it } from 'vitest';
import { baseOptions, colorAt, CHART_COLORS, isEmptyChart, shortMonth, tableRows, tooltipLabel } from './chartData';

describe('chartData', () => {
    it('abrevia meses para o eixo', () => {
        expect(shortMonth('2026-03')).toBe('mar/26');
        expect(shortMonth('2025-12')).toBe('dez/25');
        expect(shortMonth('2026')).toBe('2026');
    });

    it('formata o tooltip em reais a partir de centavos', () => {
        const label = tooltipLabel({ dataset: { label: 'Gastos' }, parsed: { y: 123456 } });

        expect(label.replace(/\s/g, ' ')).toBe('Gastos: R$ 1.234,56');
        expect(tooltipLabel({ dataset: {}, label: 'Seguro', parsed: 5000 }).replace(/\s/g, ' ')).toBe('Seguro: R$ 50,00');
    });

    it('usa a mesma paleta de forma cíclica', () => {
        expect(colorAt(0)).toBe(CHART_COLORS[0]);
        expect(colorAt(CHART_COLORS.length)).toBe(CHART_COLORS[0]);
    });

    it('gera linhas da tabela acessível e detecta gráfico vazio', () => {
        const rows = tableRows(['jan', 'fev'], [{ values: [100, 250] }]);

        expect(rows[1][0]).toBe('fev');
        expect(rows[1][1].replace(/\s/g, ' ')).toBe('R$ 2,50');
        expect(isEmptyChart([{ values: [0, 0] }])).toBe(true);
        expect(isEmptyChart([{ values: [0, 5] }])).toBe(false);
        expect(isEmptyChart([])).toBe(true);
    });

    it('opções são responsivas e escondem eixos para o donut', () => {
        const options = baseOptions();
        const donut = baseOptions({ axes: false });

        expect(options.responsive).toBe(true);
        expect(options.maintainAspectRatio).toBe(false);
        expect(options.scales.y.ticks.callback(1000).replace(/\s/g, ' ')).toBe('R$ 10,00');
        expect(donut.scales).toBeUndefined();
    });
});
