<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rating' => ['present', 'nullable', 'numeric', 'between:0,5.0', 'multiple_of:0.5'],
        ];
    }

    /**
     * Devuelve el valor normalizado de la puntuación (`0.5`..`5.0`), o `null`
     * cuando el usuario envía `null` o `0` para eliminar su calificación.
     */
    public function ratingValue(): ?float
    {
        $value = $this->validated('rating');

        if ($value === null || (float) $value === 0.0) {
            return null;
        }

        return (float) $value;
    }
}
