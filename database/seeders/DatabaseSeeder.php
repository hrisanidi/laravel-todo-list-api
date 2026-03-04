<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Note;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Admin
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        Note::factory(15)->create([
            'user_id' => $admin->id
        ]);

        // Regular users
        $users = User::factory(5)->create();

        foreach ($users as $user) {
            Note::factory(10)->create([
                'user_id' => $user->id
            ]);
        }
    }
}
