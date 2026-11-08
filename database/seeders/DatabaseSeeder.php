<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            GenreSeeder::class,
            AuthorSeeder::class,
            BookSeeder::class,
            LibraryEntrySeeder::class,
            ReviewSeeder::class,
            CommentSeeder::class,
        ]);

        $this->seedSocialInteractions();
    }

    /**
     * Add complementary social graph interactions (follows & review likes)
     * preserving the curated catalog and user base.
     */
    private function seedSocialInteractions(): void
    {
        $curatedUsers = User::query()->whereIn('id', [1, 2, 3, 4])->get()->keyBy('id');

        if ($curatedUsers->count() === 4) {
            $curatedUsers[1]->following()->syncWithoutDetaching([2, 3, 4]);
            $curatedUsers[2]->following()->syncWithoutDetaching([1, 3]);
            $curatedUsers[3]->following()->syncWithoutDetaching([1]);
            $curatedUsers[4]->following()->syncWithoutDetaching([1, 2]);
        }

        $communityUsers = User::factory(3)->create();
        $reviews = Review::all();

        foreach ($communityUsers as $member) {
            $member->assignRole('user');
            $member->following()->syncWithoutDetaching([1]);

            if ($reviews->isNotEmpty()) {
                $member->likedReviews()->syncWithoutDetaching(
                    $reviews->pluck('id')->all()
                );
            }
        }

        if ($reviews->isNotEmpty() && isset($curatedUsers[2], $curatedUsers[4])) {
            $curatedUsers[2]->likedReviews()->syncWithoutDetaching([$reviews->first()->id]);
            $curatedUsers[4]->likedReviews()->syncWithoutDetaching([$reviews->first()->id]);
        }
    }
}
