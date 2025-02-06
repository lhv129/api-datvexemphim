<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        // Role::firstOrCreate([
        //     'id' => 1,
        //     'name' => 'Admin', // Hoặc tên vai trò bạn muốn
        // ]);
        // User::create([
        //     'name' => 'NInhDuy',
        //     'role_id' => '1',
        //     'fileName' => 'default-avatar.png',
        //     'email' => 'ninhduy@gmail.com',
        //     'password'=>Hash::make('123123123'),
        // ]);
    }
}
