<?php

namespace App\Http\Requests;

use App\Models\ExpenseCategory;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class ExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');

        $this->merge([
            'name' => is_string($name) ? trim($name) : $name,
            'color' => $this->filled('color') ? $this->input('color') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var ExpenseCategory|null $category */
        $category = $this->route('category');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Nome único ignorando maiúsculas/minúsculas.
                function (string $attribute, mixed $value, Closure $fail) use ($category): void {
                    $exists = ExpenseCategory::query()
                        ->whereRaw('lower(name) = ?', [mb_strtolower((string) $value)])
                        ->when($category, fn ($query) => $query->whereKeyNot($category->getKey()))
                        ->exists();

                    if ($exists) {
                        $fail('Já existe uma categoria com este nome.');
                    }
                },
            ],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'color' => 'cor',
            'active' => 'ativa',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'color.regex' => 'Informe a cor no formato #rrggbb.',
        ];
    }
}
