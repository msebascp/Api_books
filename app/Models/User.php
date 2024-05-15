<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    public function read_books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'read_lists', 'user_id', 'book_id')
            ->orderByPivot('created_at', 'desc');
    }

    public function collection_books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'collection_lists', 'user_id', 'book_id')
            ->orderByPivot('created_at', 'desc');
    }

    public function watch_books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'watch_lists', 'user_id', 'book_id')
            ->orderByPivot('created_at', 'desc');
    }

    public function like_books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'read_lists', 'user_id', 'book_id')
            ->wherePivot('is_like', true)
            ->orderByPivot('created_at', 'desc');
    }

    // Relación de seguidores (usuarios que siguen a este usuario)
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'user_id', 'follower_id')
            ->withTimestamps();
    }

    // Relación de usuarios seguidos por este usuario
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
