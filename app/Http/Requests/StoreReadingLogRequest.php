<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReadingLogRequest extends FormRequest
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
        $finishedAtRules = ['nullable', 'date'];

        if ($this->filled('started_at')) {
            $finishedAtRules[] = 'after_or_equal:started_at';
        }

        return [
            'started_at' => ['nullable', 'date'],
            'finished_at' => $finishedAtRules,
            'is_reread' => ['sometimes', 'boolean'],
        ];
    }
}
