<?php

namespace App\Http\Controllers;

use App\Domain\Reporting\ReportBuilder;
use App\Domain\Reporting\ReportCsv;
use App\Domain\Reporting\ReportRegistry;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    /**
     * Exporta o relatório com os filtros da URL, em streaming, sempre com todas as linhas
     * do recorte (a paginação existe só na tela).
     */
    public function __invoke(Request $request, string $report, ReportRegistry $registry, ReportBuilder $builder, ReportCsv $csv): StreamedResponse
    {
        $definition = $registry->find($report);

        abort_if($definition === null, 404);

        $result = $builder->build($definition, $request->query());
        $columns = $result['columns'];

        return response()->streamDownload(function () use ($csv, $columns, $result): void {
            $out = fopen('php://output', 'w');

            fwrite($out, ReportCsv::BOM);
            fputcsv($out, $csv->header($columns), ReportCsv::DELIMITER, '"', '', "\r\n");

            foreach ($result['rows'] as $row) {
                fputcsv($out, $csv->line($columns, $row), ReportCsv::DELIMITER, '"', '', "\r\n");
            }

            fclose($out);
        }, $csv->filename($definition->key(), $result['filters'], $definition->supports()), [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
