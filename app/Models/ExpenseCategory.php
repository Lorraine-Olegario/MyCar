<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ExpenseCategory extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'color',
        'active',
        'sort_order',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'in_use',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ExpenseCategory $category): void {
            if ($category->slug === null || $category->isDirty('name')) {
                $category->slug = static::uniqueSlug($category->name, $category->id);
            }
        });
    }

    /**
     * Gera um slug único a partir do nome (sufixo numérico em caso de colisão).
     */
    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'categoria';
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Indica se há despesas usando a categoria; categorias em uso não são excluídas.
     */
    public function isInUse(): bool
    {
        return $this->expenses()->exists();
    }

    /**
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function getInUseAttribute(): bool
    {
        return $this->exists && $this->isInUse();
    }
}
