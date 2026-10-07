<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class OpenEduDevelopmentUsersSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'Conturile demonstrative nu pot fi create în mediul production.'
            );
        }

        $users = [
            [
                'name' => 'Administrator OpenEdu',
                'email' => 'admin@exemplu.ro',
                'password' => 'admin',
                'role' => UserRole::Admin,
            ],
            [
                'name' => 'Moderator OpenEdu',
                'email' => 'moderator@exemplu.ro',
                'password' => 'moderator',
                'role' => UserRole::Moderator,
            ],
            [
                'name' => 'Profesor OpenEdu',
                'email' => 'profesor@exemplu.ro',
                'password' => 'profesor',
                'role' => UserRole::Teacher,
            ],
            [
                'name' => 'Elev OpenEdu',
                'email' => 'elev@exemplu.ro',
                'password' => 'elev',
                'role' => UserRole::Learner,
            ],
            [
                'name' => 'Tutore OpenEdu',
                'email' => 'tutore@exemplu.ro',
                'password' => 'tutore',
                'role' => UserRole::Guardian,
            ],
        ];

        foreach ($users as $data) {
            $user = User::query()->firstOrNew([
                'email' => $data['email'],
            ]);

            $isNew = ! $user->exists;

            $user->name = $data['name'];
            $user->role = $data['role'];
            $user->is_active = true;

            if ($isNew) {
                $user->password = $data['password'];
            }

            $user->save();
        }
    }
}
