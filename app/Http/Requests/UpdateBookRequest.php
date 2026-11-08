<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
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
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $book = $this->route('book');
        $bookId = $book instanceof Book ? $book->id : $book;

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cover_path' => ['nullable', 'string', 'max:255'],
            'isbn' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('books', 'isbn')->ignore($bookId),
            ],
            'publication_date' => ['nullable', 'date'],
            'pages' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'authors' => ['sometimes', 'array'],
            'authors.*' => ['integer', Rule::exists('authors', 'id')],
            'genres' => ['sometimes', 'array'],
            'genres.*' => ['integer', Rule::exists('genres', 'id')],
        ];
    }
}
