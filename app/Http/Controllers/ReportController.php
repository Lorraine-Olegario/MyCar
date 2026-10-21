<?php

namespace App\Http\Controllers;

use App\Domain\Reporting\FinancialQueries;
use App\Domain\Reporting\ReportBuilder;
use App\Domain\Reporting\ReportRegistry;
use App\Models\ExpenseCategory;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public const PER_PAGE = 20;

    public function index(ReportRegistry $registry): Response
    {
        return Inertia::render('Reports/Index', [
            'reports' => array_map(fn ($report) => [
                'key' => $report->key(),
                'title' => $report->title(),
                'description' => $report->description(),
            ], $registry->all()),
        ]);
    }

    public function show(Request $request, string $report, ReportRegistry $registry, ReportBuilder $builder, FinancialQueries $queries): Response
    {
        $definition = $registry->find($report);

        abort_if($definition === null, 404);

        $result = $builder->build($definition, $request->query());

        $rows = $result['rows'];
        $pagination = null;

        if ($definition->paginated()) {
            $page = max(1, (int) $request->query('page', 1));
            $paginator = new LengthAwarePaginator(
                array_slice($rows, ($page - 1) * self::PER_PAGE, self::PER_PAGE),
                count($rows),
                self::PER_PAGE,
                $page,
                ['path' => $request->url(), 'query' => $request->query()],
            );

            $rows = $paginator->items();
            $pagination = ['links' => $paginator->linkCollection()->all(), 'from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'total' => $paginator->total()];
        }

        return Inertia::render('Reports/Show', [
            'report' => [
                'key' => $definition->key(),
                'title' => $definition->title(),
                'description' => $definition->description(),
                'supports' => $definition->supports(),
            ],
            'filters' => $result['echo'],
            'applied' => $result['applied'],
            'columns' => $result['columns'],
            'rows' => $rows,
            'total_rows' => count($result['rows']),
            'totals' => $result['totals'],
            'chart' => $result['chart'],
            'pagination' => $pagination,
            'completeness' => $definition->supports()['period'] ? $queries->completeness($result['filters']) : null,
            'options' => [
                'vehicles' => Vehicle::query()->orderBy('name')->get(['id', 'name', 'brand', 'model', 'version'])
                    ->map(fn (Vehicle $vehicle) => ['value' => $vehicle->id, 'label' => $vehicle->full_name])->values()->all(),
                'categories' => ExpenseCategory::query()->ordered()->get(['id', 'name'])
                    ->map(fn (ExpenseCategory $category) => ['value' => $category->id, 'label' => $category->name])->values()->all(),
            ],
        ]);
    }
}
