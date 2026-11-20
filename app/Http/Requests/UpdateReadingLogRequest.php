<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\ReadingLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateReadingLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        $readingLog = $this->route('readingLog');

        return $readingLog instanceof ReadingLog
            && $this->user()?->id === $readingLog->user_id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'started_at' => ['nullable', 'date'],
            'finished_at' => ['nullable', 'date'],
            'is_reread' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var ReadingLog|null $readingLog */
            $readingLog = $this->route('readingLog');

            $startedAt = $this->has('started_at')
                ? $this->input('started_at')
                : $readingLog?->started_at?->format('Y-m-d');

            $finishedAt = $this->has('finished_at')
                ? $this->input('finished_at')
                : $readingLog?->finished_at?->format('Y-m-d');

            if (
                is_string($startedAt)
                && is_string($finishedAt)
                && strtotime($finishedAt) < strtotime($startedAt)
            ) {
                $validator->errors()->add(
                    'finished_at',
                    'La fecha de finalización debe ser igual o posterior a la fecha de inicio.'
                );
            }
        });
    }
}
