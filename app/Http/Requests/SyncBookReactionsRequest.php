<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ReactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncBookReactionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * `reactions` debe venir siempre (aunque sea vacío, para borrar todas):
     * el endpoint PUT representa el estado completo e idempotente.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reactions' => ['present', 'array', 'list', 'max:'.ReactionType::MAX_PER_USER],
            'reactions.*' => ['required', 'string', 'distinct', Rule::enum(ReactionType::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reactions.present' => 'Debes enviar el campo reactions (puede ser un array vacío).',
            'reactions.max' => 'Solo puedes añadir un máximo de :max reacciones por libro.',
            'reactions.*.distinct' => 'No puedes repetir la misma reacción.',
            'reactions.*.enum' => 'La reacción seleccionada no es válida. Valores permitidos: '
                .implode(', ', ReactionType::values()).'.',
        ];
    }

    /**
     * Reacciones validadas como instancias del Enum.
     *
     * @return list<ReactionType>
     */
    public function reactions(): array
    {
        /** @var list<string> $values */
        $values = $this->validated('reactions', []);

        return array_map(ReactionType::from(...), $values);
    }
}
