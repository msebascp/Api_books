<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('comments')->insert([
            [
                'user_id' => 2,
                'review_id' => 1,
                'content' => 'Me encantan las historias de Sherlock Holmes, siempre me sorprende cómo logra resolver los casos más complicados',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('comments')->insert([
            [
                'user_id' => 3,
                'review_id' => 1,
                'content' => 'Siempre hay grandes citas en los libros de Sherlock Holmes, es un personaje muy interesante',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
