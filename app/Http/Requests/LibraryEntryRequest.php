<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ReadingStatus;
use App\Models\LibraryEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LibraryEntryRequest extends FormRequest
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
        $entryExists = LibraryEntry::query()
            ->where('user_id', $this->user()?->id)
            ->where('book_id', $this->input('book_id'))
            ->exists();

        return [
            'book_id' => ['required', 'integer', Rule::exists('books', 'id')],
            'status' => [
                Rule::requiredIf(! $entryExists),
                Rule::enum(ReadingStatus::class),
            ],
            'is_owned' => ['sometimes', 'boolean'],
            'is_favorite' => ['sometimes', 'boolean'],
            'read_at' => ['nullable', 'date'],
        ];
    }
}
