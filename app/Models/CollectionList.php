<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionList extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_list_id',
        'book_id',
    ];
}
