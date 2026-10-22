<?php

namespace App\Domain\Expenses;

use App\Models\Expense;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Guarda os comprovantes das despesas no disco privado, em `expenses/{id}/...`.
 * O acesso passa sempre pela rota `expenses.receipt`; nada vai para `public/`.
 */
class ExpenseReceiptStorage
{
    public const DISK = 'local';

    /**
     * Grava o comprovante, apagando o anterior antes (se existir).
     *
     * @return string caminho relativo dentro do disco privado
     */
    public function put(Expense $expense, UploadedFile $receipt): string
    {
        $this->forget($expense);

        return (string) $receipt->storeAs(
            'expenses/'.$expense->getKey(),
            Str::uuid()->toString().'.'.($receipt->extension() ?: 'pdf'),
            self::DISK,
        );
    }

    public function forget(Expense $expense): void
    {
        if (filled($expense->receipt_path)) {
            Storage::disk(self::DISK)->delete($expense->receipt_path);
        }
    }

    public function exists(Expense $expense): bool
    {
        return filled($expense->receipt_path)
            && Storage::disk(self::DISK)->exists($expense->receipt_path);
    }
}
