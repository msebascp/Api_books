<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'image_path'];

    protected $hidden = ['pivot'];

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class)
            ->withTimestamps();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)
            ->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class)
            ->withTimestamps();
    }

    public function users_read()
    {
        return $this->belongsToMany(User::class, 'read_list_user_books', 'book_id', 'user_id')
            ->withTimestamps();
    }
}
