import { formatCurrency, formatMonthYear } from './format';

/**
 * Paleta única dos gráficos (laranja da marca primeiro, depois cores bem distinguíveis).
 */
export const CHART_COLORS = [
    '#c2410c',
    '#2563eb',
    '#059669',
    '#7c3aed',
    '#d97706',
    '#0891b2',
    '#be185d',
    '#65a30d',
    '#64748b',
    '#dc2626',
];

export function colorAt(index) {
    return CHART_COLORS[index % CHART_COLORS.length];
}

/**
 * Rótulo curto de um mês "YYYY-MM" para o eixo (ex.: "2026-03" -> "mar/26").
 */
export function shortMonth(value) {
    const match = /^(\d{4})-(\d{2})/.exec(String(value));

    if (!match) {
        return String(value);
    }

    const label = formatMonthYear(`${match[1]}-${match[2]}`).split(' ')[0].slice(0, 3);

    return `${label}/${match[1].slice(2)}`;
}

/**
 * Texto do tooltip: "Série: R$ 1.234,56" (valores em centavos).
 */
export function tooltipLabel(context, formatter = formatCurrency) {
    const value = context.parsed?.y ?? context.parsed;
    const name = context.dataset?.label ?? context.label ?? '';

    return name ? `${name}: ${formatter(value)}` : formatter(value);
}

/**
 * Converte linhas {label, value} em linhas de tabela formatadas (fallback acessível).
 */
export function tableRows(labels, series, formatter = formatCurrency) {
    return labels.map((label, index) => [
        label,
        ...series.map((serie) => formatter(serie.values[index] ?? 0)),
    ]);
}

/**
 * Indica se não há nada a desenhar: sem rótulos ou com todos os valores nulos/zero.
 */
export function isEmptyChart(series) {
    return series.every((serie) => serie.values.every((value) => !value));
}

/**
 * Opções comuns: responsivo, sem animação longa, tooltip em R$ e legenda acessível.
 */
export function baseOptions({ formatter = formatCurrency, legend = true, axes = true } = {}) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 250 },
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: legend, position: 'bottom', labels: { boxWidth: 12, usePointStyle: true } },
            tooltip: { callbacks: { label: (context) => tooltipLabel(context, formatter) } },
        },
        ...(axes
            ? {
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, ticks: { callback: (value) => formatter(value) } },
                },
            }
            : {}),
    };
}
