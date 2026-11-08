<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('1234appbooks');

        $users = [
            [
                'name' => 'Manuel Sebastián',
                'username' => 'sebas095',
                'email' => 'msebascp@gmail.com',
                'avatar_path' => 'https://avatars.githubusercontent.com/u/113929087?v=4',
                'bio' => 'Lector empedernido de misterio, ciencia ficción y fantasía épica.',
                'password' => $password,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Óscar Damián',
                'username' => 'oscar2007',
                'email' => 'oscar@appbooks.com',
                'avatar_path' => 'https://walter.trakt.tv/images/users/009/153/416/avatars/medium/98888d7f01.jpg',
                'bio' => 'Explorando clásicos universales y grandes sagas literarias.',
                'password' => $password,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Francisco Jesús',
                'username' => 'frambor',
                'email' => 'fran@appbooks.com',
                'avatar_path' => 'https://pbs.twimg.com/profile_images/1270142787799965698/8l-wD4kY_400x400.jpg',
                'bio' => 'Aficionado a las novelas policíacas y la historia.',
                'password' => $password,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Alejandro José',
                'username' => 'alejo',
                'email' => 'alex@appbooks.com',
                'avatar_path' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSMohk65yZ3P2omqX19bKqvVY1AGzDGF8NBv1QGH60ZZVd-KWFeHbPghx-iH0rd6mYiO0s',
                'bio' => 'Coleccionista de ediciones físicas y narrativa contemporánea.',
                'password' => $password,
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $index => $userData) {
            $user = User::create($userData);
            $user->assignRole($index === 0 ? 'admin' : 'user');
        }
    }
}
