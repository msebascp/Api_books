<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $book = $this->route('book');
        $bookId = $book instanceof Book ? $book->id : $book;

        if ($bookId !== null) {
            $this->merge(['book_id' => (int) $bookId]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'book_id' => [
                'required',
                'integer',
                Rule::exists('books', 'id'),
                Rule::unique('reviews', 'book_id')->where('user_id', $this->user()?->id),
            ],
            'rating' => ['required', 'numeric', 'between:0.5,5.0'],
            'content' => ['nullable', 'string'],
            'has_spoilers' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'book_id.unique' => 'Ya has publicado una reseña para este libro.',
        ];
    }
}
