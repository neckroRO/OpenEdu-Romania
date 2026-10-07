<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_central_users(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Administrator OpenEdu',
        ]);

        $teacher = User::factory()->teacher()->create([
            'name' => 'Profesor Test',
        ]);

        $moderator = User::factory()->moderator()->create([
            'name' => 'Moderator Test',
        ]);

        User::factory()->create([
            'name' => 'Elev Local',
            'role' => UserRole::Learner,
        ]);

        $token = $admin
            ->createToken('OpenEdu Test')
            ->plainTextToken;

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/admin/users');

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $admin->id,
                'name' => 'Administrator OpenEdu',
                'role' => UserRole::Admin->value,
            ])
            ->assertJsonFragment([
                'id' => $teacher->id,
                'name' => 'Profesor Test',
                'role' => UserRole::Teacher->value,
            ])
            ->assertJsonFragment([
                'id' => $moderator->id,
                'name' => 'Moderator Test',
                'role' => UserRole::Moderator->value,
            ])
            ->assertJsonMissing([
                'name' => 'Elev Local',
            ]);
    }

    public function test_non_admin_cannot_list_users(): void
    {
        $teacher = User::factory()
            ->teacher()
            ->create();

        $token = $teacher
            ->createToken('OpenEdu Test')
            ->plainTextToken;

        $this
            ->withToken($token)
            ->getJson('/api/v1/admin/users')
            ->assertForbidden();
    }

    public function test_admin_can_create_teacher(): void
    {
        $admin = User::factory()
            ->admin()
            ->create();

        $token = $admin
            ->createToken('OpenEdu Test')
            ->plainTextToken;

        $response = $this
            ->withToken($token)
            ->postJson('/api/v1/admin/users', [
                'name' => 'Maria Popescu',
                'email' => 'maria.popescu@openedu.ro',
                'password' => 'Parola-Sigura-2026',
                'password_confirmation' => 'Parola-Sigura-2026',
                'role' => UserRole::Teacher->value,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.name',
                'Maria Popescu'
            )
            ->assertJsonPath(
                'data.email',
                'maria.popescu@openedu.ro'
            )
            ->assertJsonPath(
                'data.role',
                UserRole::Teacher->value
            );

        $user = User::query()
            ->where(
                'email',
                'maria.popescu@openedu.ro'
            )
            ->firstOrFail();

        $this->assertSame(
            UserRole::Teacher,
            $user->role
        );

        $this->assertTrue(
            Hash::check(
                'Parola-Sigura-2026',
                $user->password
            )
        );
    }

    public function test_admin_cannot_create_local_user_role(): void
    {
        $admin = User::factory()
            ->admin()
            ->create();

        $token = $admin
            ->createToken('OpenEdu Test')
            ->plainTextToken;

        $response = $this
            ->withToken($token)
            ->postJson('/api/v1/admin/users', [
                'name' => 'Elev Test',
                'email' => 'elev@openedu.ro',
                'password' => 'Parola-Sigura-2026',
                'password_confirmation' => 'Parola-Sigura-2026',
                'role' => UserRole::Learner->value,
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath(
                'error.code',
                'VALIDATION_FAILED'
            );

        $this->assertDatabaseMissing('users', [
            'email' => 'elev@openedu.ro',
        ]);
    }

    public function test_unauthenticated_user_cannot_list_users(): void
    {
        $this
            ->getJson('/api/v1/admin/users')
            ->assertUnauthorized()
            ->assertJsonPath(
                'error.code',
                'UNAUTHENTICATED'
            );
    }

    public function test_non_admin_cannot_create_user(): void
    {
        $teacher = User::factory()
            ->teacher()
            ->create();

        $token = $teacher
            ->createToken('OpenEdu Test')
            ->plainTextToken;

        $this
            ->withToken($token)
            ->postJson('/api/v1/admin/users', [
                'name' => 'Profesor Nou',
                'email' => 'profesor.nou@openedu.ro',
                'password' => 'Parola-Sigura-2026',
                'password_confirmation' => 'Parola-Sigura-2026',
                'role' => UserRole::Teacher->value,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'email' => 'profesor.nou@openedu.ro',
        ]);
    }

    public function test_admin_cannot_create_duplicate_email(): void
    {
        $admin = User::factory()
            ->admin()
            ->create();

        User::factory()
            ->teacher()
            ->create([
                'email' => 'existent@openedu.ro',
            ]);

        $token = $admin
            ->createToken('OpenEdu Test')
            ->plainTextToken;

        $this
            ->withToken($token)
            ->postJson('/api/v1/admin/users', [
                'name' => 'Alt Profesor',
                'email' => 'existent@openedu.ro',
                'password' => 'Parola-Sigura-2026',
                'password_confirmation' => 'Parola-Sigura-2026',
                'role' => UserRole::Teacher->value,
            ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'error.code',
                'VALIDATION_FAILED'
            );
    }

    public function test_admin_cannot_create_invalid_role(): void
    {
        $admin = User::factory()
            ->admin()
            ->create();

        $token = $admin
            ->createToken('OpenEdu Test')
            ->plainTextToken;

        $this
            ->withToken($token)
            ->postJson('/api/v1/admin/users', [
                'name' => 'Utilizator Invalid',
                'email' => 'invalid@openedu.ro',
                'password' => 'Parola-Sigura-2026',
                'password_confirmation' => 'Parola-Sigura-2026',
                'role' => 'superadmin',
            ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'error.code',
                'VALIDATION_FAILED'
            );

        $this->assertDatabaseMissing('users', [
            'email' => 'invalid@openedu.ro',
        ]);
    }
}
