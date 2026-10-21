<?php

namespace App\Domain\Maintenance;

use App\Domain\Expenses\ExpenseReceiptStorage;
use App\Models\Expense;
use App\Models\Maintenance;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

/**
 * Mantém manutenção e despesa como um par 1:1: tudo é criado, alterado e removido em
 * uma única transação, para nunca haver manutenção órfã nem despesa pela metade.
 */
class MaintenanceService
{
    public function __construct(private readonly ExpenseReceiptStorage $receipts) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Maintenance
    {
        return DB::transaction(function () use ($data): Maintenance {
            $expense = Expense::create($this->expenseData($data));

            $maintenance = Maintenance::create([
                ...$this->maintenanceData($data),
                'vehicle_id' => $expense->vehicle_id,
                'expense_id' => $expense->id,
            ]);

            $this->raiseVehicleOdometer($maintenance);

            return $maintenance;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Maintenance $maintenance, array $data): Maintenance
    {
        return DB::transaction(function () use ($maintenance, $data): Maintenance {
            $maintenance->expense->update($this->expenseData($data));
            $maintenance->update([...$this->maintenanceData($data), 'vehicle_id' => $maintenance->expense->vehicle_id]);

            $this->raiseVehicleOdometer($maintenance);

            return $maintenance;
        });
    }

    /**
     * Exclui a manutenção e a despesa vinculada (inclusive o comprovante em disco).
     */
    public function delete(Maintenance $maintenance): void
    {
        DB::transaction(function () use ($maintenance): void {
            $expense = $maintenance->expense;

            $maintenance->delete();
            $this->receipts->forget($expense);
            $expense->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function expenseData(array $data): array
    {
        return [
            'vehicle_id' => $data['vehicle_id'],
            'expense_category_id' => $data['expense_category_id'],
            'description' => $data['description'],
            'amount_cents' => $data['amount_cents'],
            'occurred_on' => $data['occurred_on'],
            'odometer' => $data['odometer'] ?? null,
            'vendor' => $data['vendor'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function maintenanceData(array $data): array
    {
        return [
            'type' => $data['type'],
            'odometer' => $data['odometer'] ?? null,
            'next_due_on' => $data['next_due_on'] ?? null,
            'next_due_odometer' => $data['next_due_odometer'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function raiseVehicleOdometer(Maintenance $maintenance): void
    {
        if ($maintenance->odometer === null) {
            return;
        }

        Vehicle::query()
            ->whereKey($maintenance->vehicle_id)
            ->where('odometer', '<', $maintenance->odometer)
            ->update(['odometer' => $maintenance->odometer]);
    }
}
