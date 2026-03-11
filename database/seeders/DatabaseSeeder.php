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

        $this->call(RoleSeeder::class);

        User::factory()->create([
            'name' => 'Admin',
            'role_id' => 1,
            'email' => 'admin',
            'password' => 'admin',
            'avatar_path' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRW0urA9Fc1ilMIytV0ofKkICyMqXMcTVutwA&s'
        ]);

        User::factory()->create([
            'name' => 'Usuario',
            'email' => 'user',
            'password' => 'user',
            'avatar_path' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRW0urA9Fc1ilMIytV0ofKkICyMqXMcTVutwA&s'
        ]);

    }
}
