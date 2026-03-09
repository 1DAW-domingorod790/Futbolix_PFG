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
            'email' => 'admin@admin',
            'password' => 'admin',
            'avatar_path' => 'https://api.dicebear.com/9.x/micah/svg?seed=Domingo%202&backgroundColor=23ca35,003b8c&backgroundType=gradientLinear&glassesProbability=50'
        ]);
    }
}
