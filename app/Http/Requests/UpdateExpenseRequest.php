<?php

namespace App\Http\Requests;

class UpdateExpenseRequest extends StoreExpenseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'remove_receipt' => ['sometimes', 'boolean'],
        ]);
    }
}
