<?php

namespace App\Http\Requests;

class UpdateVehicleRequest extends StoreVehicleRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'remove_photo' => ['sometimes', 'boolean'],
        ]);
    }
}
