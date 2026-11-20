<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\BookList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddBookToListRequest extends FormRequest
{
    public function authorize(): bool
    {
        $bookList = $this->route('bookList');

        return $bookList instanceof BookList
            && $this->user()?->id === $bookList->user_id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var BookList|null $bookList */
        $bookList = $this->route('bookList');

        return [
            'book_id' => [
                'required',
                'integer',
                Rule::exists('books', 'id')->whereNull('deleted_at'),
                Rule::unique('book_list_items', 'book_id')->where('book_list_id', $bookList?->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'book_id.unique' => 'El libro ya se encuentra en esta lista.',
        ];
    }
}
