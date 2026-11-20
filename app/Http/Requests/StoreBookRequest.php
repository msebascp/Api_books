<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('author_ids') && ! $this->has('authors')) {
            $this->merge(['authors' => $this->input('author_ids')]);
        }

        if ($this->has('genre_ids') && ! $this->has('genres')) {
            $this->merge(['genres' => $this->input('genre_ids')]);
        }

        $isbn = $this->input('isbn');

        if (is_string($isbn)) {
            $normalized = strtoupper((string) preg_replace('/[\s-]+/', '', $isbn));
            $this->merge(['isbn' => $normalized !== '' ? $normalized : null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'cover_path' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:20', Rule::unique('books', 'isbn')],
            'publication_date' => ['nullable', 'date'],
            'pages' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'authors' => ['sometimes', 'array'],
            'authors.*' => ['integer', Rule::exists('authors', 'id')],
            'genres' => ['sometimes', 'array'],
            'genres.*' => ['integer', Rule::exists('genres', 'id')],
        ];
    }
}
