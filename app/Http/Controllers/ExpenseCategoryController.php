<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseCategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Categories/Index', [
            'categories' => ExpenseCategory::query()->ordered()->get(),
        ]);
    }

    public function store(ExpenseCategoryRequest $request): RedirectResponse
    {
        $category = ExpenseCategory::create([
            ...$request->validated(),
            'sort_order' => (int) ExpenseCategory::query()->max('sort_order') + 1,
        ]);

        return to_route('categories.index')
            ->with('success', "Categoria {$category->name} criada com sucesso.");
    }

    public function update(ExpenseCategoryRequest $request, ExpenseCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        return to_route('categories.index')
            ->with('success', "Categoria {$category->name} atualizada com sucesso.");
    }

    /**
     * Ativa ou desativa a categoria; despesas antigas continuam válidas.
     */
    public function toggle(ExpenseCategory $category): RedirectResponse
    {
        $category->update(['active' => ! $category->active]);

        return to_route('categories.index')->with(
            'success',
            $category->active
                ? "Categoria {$category->name} ativada."
                : "Categoria {$category->name} desativada. Despesas antigas continuam válidas.",
        );
    }

    /**
     * Move a categoria uma posição para cima ou para baixo.
     */
    public function move(Request $request, ExpenseCategory $category): RedirectResponse
    {
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        DB::transaction(function () use ($category, $direction): void {
            $ordered = ExpenseCategory::query()->ordered()->get()->values();
            $index = $ordered->search(fn (ExpenseCategory $item) => $item->is($category));
            $target = $direction === 'up' ? $index - 1 : $index + 1;

            if ($index === false || ! isset($ordered[$target])) {
                return;
            }

            $ids = $ordered->pluck('id')->all();
            [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];

            foreach ($ids as $position => $id) {
                ExpenseCategory::query()->whereKey($id)->update(['sort_order' => $position + 1]);
            }
        });

        return to_route('categories.index');
    }

    /**
     * Exclui apenas categorias sem uso; em uso, orienta a desativar.
     */
    public function destroy(ExpenseCategory $category): RedirectResponse
    {
        if ($category->isInUse()) {
            return to_route('categories.index')->with(
                'error',
                "A categoria {$category->name} está em uso por despesas e não pode ser excluída. Desative-a para impedir novos lançamentos.",
            );
        }

        $category->delete();

        return to_route('categories.index')->with('success', "Categoria {$category->name} excluída.");
    }
}
