<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserPasswordEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reset_central_user_password(): void
    {
        $admin = User::factory()
            ->admin()
            ->create();

        $teacher = User::factory()
            ->teacher()
            ->create([
                'email' => 'profesor@openedu.ro',
                'password' => 'Parola-Veche-2026',
            ]);

        $token = $admin
            ->createToken('OpenEdu Test')
            ->plainTextToken;

        $response = $this
            ->withToken($token)
            ->putJson(
                "/api/v1/admin/users/{$teacher->id}/password",
                [
                    'password' => 'Parola-Noua-2026',
                    'password_confirmation' => 'Parola-Noua-2026',
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.message',
                'Parola utilizatorului a fost resetată.'
            );

        $teacher->refresh();

        $this->assertTrue(
            Hash::check(
                'Parola-Noua-2026',
                $teacher->password
            )
        );

        $this->assertFalse(
            Hash::check(
                'Parola-Veche-2026',
                $teacher->password
            )
        );
    }

    public function test_password_reset_revokes_existing_tokens(): void
    {
        $admin = User::factory()
            ->admin()
            ->create();

        $teacher = User::factory()
            ->teacher()
            ->create();

        $teacherToken = $teacher
            ->createToken('Teacher Session')
            ->plainTextToken;

        $adminToken = $admin
            ->createToken('Admin Session')
            ->plainTextToken;

        $this
            ->withToken($adminToken)
            ->putJson(
                "/api/v1/admin/users/{$teacher->id}/password",
                [
                    'password' => 'Parola-Noua-2026',
                    'password_confirmation' => 'Parola-Noua-2026',
                ]
            )
            ->assertOk();

        $this->assertDatabaseMissing(
            'personal_access_tokens',
            [
                'tokenable_id' => $teacher->id,
                'tokenable_type' => User::class,
            ]
        );

        Auth::forgetGuards();

        $this
            ->withToken($teacherToken)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_non_admin_cannot_reset_password(): void
    {
        $teacher = User::factory()
            ->teacher()
            ->create();

        $otherTeacher = User::factory()
            ->teacher()
            ->create();

        $token = $teacher
            ->createToken('OpenEdu Test')
            ->plainTextToken;

        $this
            ->withToken($token)
            ->putJson(
                "/api/v1/admin/users/{$otherTeacher->id}/password",
                [
                    'password' => 'Parola-Noua-2026',
                    'password_confirmation' => 'Parola-Noua-2026',
                ]
            )
            ->assertForbidden();
    }

    public function test_admin_cannot_reset_local_user_password(): void
    {
        $admin = User::factory()
            ->admin()
            ->create();

        $learner = User::factory()
            ->create([
                'role' => UserRole::Learner,
            ]);

        $token = $admin
            ->createToken('OpenEdu Test')
            ->plainTextToken;

        $this
            ->withToken($token)
            ->putJson(
                "/api/v1/admin/users/{$learner->id}/password",
                [
                    'password' => 'Parola-Noua-2026',
                    'password_confirmation' => 'Parola-Noua-2026',
                ]
            )
            ->assertNotFound();
    }

    public function test_password_must_be_confirmed(): void
    {
        $admin = User::factory()
            ->admin()
            ->create();

        $teacher = User::factory()
            ->teacher()
            ->create();

        $token = $admin
            ->createToken('OpenEdu Test')
            ->plainTextToken;

        $this
            ->withToken($token)
            ->putJson(
                "/api/v1/admin/users/{$teacher->id}/password",
                [
                    'password' => 'Parola-Noua-2026',
                    'password_confirmation' => 'Alta-Parola-2026',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'error.code',
                'VALIDATION_FAILED'
            );
    }

    public function test_password_must_have_minimum_length(): void
    {
        $admin = User::factory()
            ->admin()
            ->create();

        $teacher = User::factory()
            ->teacher()
            ->create();

        $token = $admin
            ->createToken('OpenEdu Test')
            ->plainTextToken;

        $this
            ->withToken($token)
            ->putJson(
                "/api/v1/admin/users/{$teacher->id}/password",
                [
                    'password' => 'scurta',
                    'password_confirmation' => 'scurta',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'error.code',
                'VALIDATION_FAILED'
            );
    }
}
