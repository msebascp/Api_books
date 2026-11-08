<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $review = $this->route('review');

        return $review instanceof Review
            && $this->user()?->id === $review->user_id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rating' => ['sometimes', 'required', 'numeric', 'between:0.5,5.0'],
            'content' => ['nullable', 'string'],
            'has_spoilers' => ['sometimes', 'boolean'],
        ];
    }
}
