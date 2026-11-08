<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Comment;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $comments = [
            [
                'user_id' => 2,
                'review_id' => 1,
                'content' => 'Me encantan las historias de Sherlock Holmes, siempre me sorprende cómo logra resolver los casos más complicados',
            ],
            [
                'user_id' => 3,
                'review_id' => 1,
                'content' => 'Siempre hay grandes citas en los libros de Sherlock Holmes, es un personaje muy interesante',
            ],
        ];

        foreach ($comments as $commentData) {
            Comment::create($commentData);
        }
    }
}
