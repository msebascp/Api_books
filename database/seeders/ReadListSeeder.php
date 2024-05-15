<?php

namespace Database\Seeders;

use App\Http\Controllers\ReadListController;
use App\Models\ReadList;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReadListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ReadList::create([
            'user_id' => 1,
            'book_id' => 1,
            'is_like' => true
        ]);
        ReadList::create([
            'user_id' => 1,
            'book_id' => 2,
            'is_like' => true
        ]);
    }
}
