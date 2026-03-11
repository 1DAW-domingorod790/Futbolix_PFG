<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'admin',
            'avatar_path' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRW0urA9Fc1ilMIytV0ofKkICyMqXMcTVutwA&s'
        ]);
    }
}
