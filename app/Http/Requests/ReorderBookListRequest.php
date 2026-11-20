<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\BookList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

class ReorderBookListRequest extends FormRequest
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
            'book_ids' => ['required', 'array', 'list'],
            'book_ids.*' => ['required', 'integer', 'distinct'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var BookList|null $bookList */
            $bookList = $this->route('bookList');

            if ($bookList === null) {
                return;
            }

            /** @var list<int> $submittedIds */
            $submittedIds = array_map(
                static fn (mixed $id): int => (int) $id,
                (array) $this->input('book_ids', [])
            );

            $currentIds = DB::table('book_list_items')
                ->where('book_list_id', $bookList->id)
                ->pluck('book_id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all();

            $sortedSubmitted = $submittedIds;
            $sortedCurrent = $currentIds;
            sort($sortedSubmitted);
            sort($sortedCurrent);

            if ($sortedSubmitted !== $sortedCurrent) {
                $validator->errors()->add(
                    'book_ids',
                    'El listado book_ids debe contener exactamente todos los libros actuales de la lista.'
                );
            }
        });
    }
}
