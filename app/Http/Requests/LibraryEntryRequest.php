<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ReadingStatus;
use App\Models\LibraryEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LibraryEntryRequest extends FormRequest
{
    private ?LibraryEntry $existingEntry = null;

    private bool $existingEntryLoaded = false;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Recupera y cachea en la petición la entrada existente (si la hay) para
     * reutilizarla tanto en la validación como en el controlador sin duplicar
     * la consulta contra `library_entries`.
     */
    public function existingEntry(): ?LibraryEntry
    {
        if (! $this->existingEntryLoaded) {
            $this->existingEntryLoaded = true;
            $bookId = $this->input('book_id');

            $this->existingEntry = is_numeric($bookId) && $this->user() !== null
                ? LibraryEntry::query()
                    ->where('user_id', $this->user()->id)
                    ->where('book_id', (int) $bookId)
                    ->first()
                : null;
        }

        return $this->existingEntry;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'book_id' => ['required', 'integer', Rule::exists('books', 'id')],
            'status' => [
                Rule::requiredIf(fn (): bool => $this->existingEntry() === null),
                Rule::enum(ReadingStatus::class),
            ],
            'is_owned' => ['sometimes', 'boolean'],
            'is_favorite' => ['sometimes', 'boolean'],
            'read_at' => [
                'nullable',
                'date',
                'prohibited_if:status,'.ReadingStatus::ToRead->value,
            ],
        ];
    }
}
