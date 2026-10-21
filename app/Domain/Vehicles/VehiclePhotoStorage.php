<?php

namespace App\Domain\Vehicles;

use App\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Guarda as fotos dos veículos no disco privado, em `vehicles/{id}/...`.
 *
 * Nada é gravado em `public/`: o acesso passa sempre pela rota
 * `vehicles.photo`, que lê o arquivo do disco `local`.
 */
class VehiclePhotoStorage
{
    public const DISK = 'local';

    /**
     * Grava a foto do veículo, apagando a anterior antes (se existir).
     *
     * @return string caminho relativo dentro do disco privado
     */
    public function put(Vehicle $vehicle, UploadedFile $photo): string
    {
        $this->forget($vehicle);

        return (string) $photo->storeAs(
            $this->directory($vehicle),
            $this->filename($photo),
            self::DISK,
        );
    }

    /**
     * Apaga o arquivo da foto atual do veículo, se houver.
     */
    public function forget(Vehicle $vehicle): void
    {
        if (filled($vehicle->photo_path)) {
            Storage::disk(self::DISK)->delete($vehicle->photo_path);
        }
    }

    /**
     * Indica se o veículo tem uma foto de fato gravada no disco.
     */
    public function exists(Vehicle $vehicle): bool
    {
        return filled($vehicle->photo_path)
            && Storage::disk(self::DISK)->exists($vehicle->photo_path);
    }

    private function directory(Vehicle $vehicle): string
    {
        return 'vehicles/'.$vehicle->getKey();
    }

    private function filename(UploadedFile $photo): string
    {
        $extension = $photo->extension() ?: 'jpg';

        return Str::uuid()->toString().'.'.$extension;
    }
}
