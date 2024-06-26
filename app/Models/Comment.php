<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static find(string $id)
 * @method static create(array $array)
 */
class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'review_id',
        'content',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function review()
    {
        return $this->belongsTo(Review::class);
    }
}
