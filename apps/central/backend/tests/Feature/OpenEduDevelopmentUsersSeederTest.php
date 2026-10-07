<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\OpenEduDevelopmentUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OpenEduDevelopmentUsersSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_users_seeder_creates_all_demo_roles_and_is_idempotent(): void
    {
        $this->seed(OpenEduDevelopmentUsersSeeder::class);
        $this->seed(OpenEduDevelopmentUsersSeeder::class);

        $this->assertSame(5, User::query()->count());

        $expected = [
            'admin@exemplu.ro' => [
                UserRole::Admin,
                'admin',
            ],
            'moderator@exemplu.ro' => [
                UserRole::Moderator,
                'moderator',
            ],
            'profesor@exemplu.ro' => [
                UserRole::Teacher,
                'profesor',
            ],
            'elev@exemplu.ro' => [
                UserRole::Learner,
                'elev',
            ],
            'tutore@exemplu.ro' => [
                UserRole::Guardian,
                'tutore',
            ],
        ];

        foreach ($expected as $email => [$role, $password]) {
            $user = User::query()
                ->where('email', $email)
                ->firstOrFail();

            $this->assertSame(
                $role,
                $user->role
            );

            $this->assertTrue(
                $user->is_active
            );

            $this->assertTrue(
                Hash::check(
                    $password,
                    $user->password
                )
            );
        }
    }

    public function test_reseeding_does_not_restore_a_changed_password(): void
    {
        $this->seed(OpenEduDevelopmentUsersSeeder::class);

        $admin = User::query()
            ->where('email', 'admin@exemplu.ro')
            ->firstOrFail();

        $admin->password = 'parola-schimbata';
        $admin->save();

        $this->seed(OpenEduDevelopmentUsersSeeder::class);

        $admin->refresh();

        $this->assertTrue(
            Hash::check(
                'parola-schimbata',
                $admin->password
            )
        );

        $this->assertFalse(
            Hash::check(
                'admin',
                $admin->password
            )
        );
    }
}
