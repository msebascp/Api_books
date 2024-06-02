<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use function Laravel\Prompts\table;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'Manuel Sebastián',
                'username' => 'sebas095',
                'email' => 'msebascp@gmail.com',
                'image_profile_path' => 'https://avatars.githubusercontent.com/u/113929087?v=4',
                'password' => Hash::make("1234appbooks"),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Óscar Damián',
                'username' => 'oscar2007',
                'email' => 'oscar@appbooks.com',
                'image_profile_path' => 'https://walter.trakt.tv/images/users/009/153/416/avatars/medium/98888d7f01.jpg',
                'password' => Hash::make("1234appbooks"),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Francisco Jesús',
                'username' => 'frambor',
                'email' => 'fran@appbooks.com',
                'image_profile_path' => 'https://avatars.githubusercontent.com/u/533658?s=64&v=4',
                'password' => Hash::make("1234appbooks"),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Alejandro José',
                'username' => 'alejo',
                'email' => 'alex@appbooks.com',
                'image_profile_path' => 'https://avatars.githubusercontent.com/u/212036?s=64&v=4',
                'password' => Hash::make("1234appbooks"),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
