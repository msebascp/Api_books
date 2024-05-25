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
                'name' => 'Sebastián',
                'username' => 'sebas095',
                'email' => 'admin@appbooks.com',
                'image_profile_path' => 'https://avatars.githubusercontent.com/u/113929087?v=4',
                'password' => Hash::make("12345678")
            ],
            [
                'name' => 'Óscar',
                'username' => 'oscar2007',
                'email' => 'oscar@appbooks.com',
                'image_profile_path' => 'https://avatars.githubusercontent.com/u/113929087?v=4',
                'password' => Hash::make("12345678")
            ],
            [
                'name' => 'Carlos',
                'username' => 'carlos001',
                'email' => 'carlos@appbooks.com',
                'image_profile_path' => 'https://avatars.githubusercontent.com/u/113929087?v=4',
                'password' => Hash::make("12345678")
            ],
        ]);
    }
}
