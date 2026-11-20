<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\BookList;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBookListRequest extends FormRequest
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
        return [
            'title' => ['sometimes', 'required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_public' => ['sometimes', 'boolean'],
            'is_ranked' => ['sometimes', 'boolean'],
        ];
    }
}
