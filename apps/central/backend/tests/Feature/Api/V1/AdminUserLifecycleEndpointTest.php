<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AdminUserLifecycleEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_central_user(): void
    {
        $admin = User::factory()
            ->admin()
            ->create();

        $teacher = User::factory()
            ->teacher()
            ->create([
                'name' => 'Profesor Inițial',
                'email' => 'initial@openedu.ro',
            ]);

        $token = $admin
            ->createToken('OpenEdu Test')
            ->plainTextToken;

        $response = $this
            ->withToken($token)
            ->patchJson(
                "/api/v1/admin/users/{$teacher->id}",
                [
                    'name' => 'Profesor Actualizat',
                    'email' => 'actualizat@openedu.ro',
                    'role' => UserRole::Moderator->value,
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.name',
                'Profesor Actualizat'
            )
            ->assertJsonPath(
                'data.email',
                'actualizat@openedu.ro'
            )
            ->assertJsonPath(
                'data.role',
                UserRole::Moderator->value
            );

        $teacher->refresh();

        $this->assertSame(
            'Profesor Actualizat',
            $teacher->name
        );

        $this->assertSame(
            'actualizat@openedu.ro',
            $teacher->email
        );

        $this->assertSame(
            UserRole::Moderator,
            $teacher->role
        );
    }

    public function test_non_admin_cannot_update_user(): void
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
            ->patchJson(
                "/api/v1/admin/users/{$otherTeacher->id}",
                [
                    'name' => 'Nume Nepermis',
                ]
            )
            ->assertForbidden();

        $otherTeacher->refresh();

        $this->assertNotSame(
            'Nume Nepermis',
            $otherTeacher->name
        );
    }

    public function test_admin_cannot_assign_local_user_role(): void
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
            ->patchJson(
                "/api/v1/admin/users/{$teacher->id}",
                [
                    'role' => UserRole::Learner->value,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'error.code',
                'VALIDATION_FAILED'
            );

        $teacher->refresh();

        $this->assertSame(
            UserRole::Teacher,
            $teacher->role
        );
    }

    public function test_admin_cannot_demote_self(): void
    {
        $admin = User::factory()
            ->admin()
            ->create();

        $token = $admin
            ->createToken('OpenEdu Test')
            ->plainTextToken;

        $this
            ->withToken($token)
            ->patchJson(
                "/api/v1/admin/users/{$admin->id}",
                [
                    'role' => UserRole::Teacher->value,
                ]
            )
            ->assertConflict()
            ->assertJsonPath(
                'error.code',
                'ADMIN_SELF_ROLE_CHANGE_NOT_ALLOWED'
            );

        $admin->refresh();

        $this->assertSame(
            UserRole::Admin,
            $admin->role
        );
    }

    public function test_admin_can_deactivate_user_and_revoke_tokens(): void
    {
        $admin = User::factory()
            ->admin()
            ->create();

        $teacher = User::factory()
            ->teacher()
            ->create([
                'is_active' => true,
            ]);

        $teacherToken = $teacher
            ->createToken('Teacher Session')
            ->plainTextToken;

        $adminToken = $admin
            ->createToken('Admin Session')
            ->plainTextToken;

        $this->assertDatabaseHas(
            'personal_access_tokens',
            [
                'tokenable_id' => $teacher->id,
                'tokenable_type' => User::class,
            ]
        );

        $response = $this
            ->withToken($adminToken)
            ->patchJson(
                "/api/v1/admin/users/{$teacher->id}",
                [
                    'is_active' => false,
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.is_active',
                false
            );

        $teacher->refresh();

        $this->assertFalse(
            $teacher->is_active
        );

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

    public function test_admin_cannot_deactivate_self(): void
    {
        $admin = User::factory()
            ->admin()
            ->create([
                'is_active' => true,
            ]);

        $token = $admin
            ->createToken('OpenEdu Test')
            ->plainTextToken;

        $this
            ->withToken($token)
            ->patchJson(
                "/api/v1/admin/users/{$admin->id}",
                [
                    'is_active' => false,
                ]
            )
            ->assertConflict()
            ->assertJsonPath(
                'error.code',
                'ADMIN_SELF_DEACTIVATION_NOT_ALLOWED'
            );

        $admin->refresh();

        $this->assertTrue(
            $admin->is_active
        );
    }

    public function test_admin_cannot_manage_local_user(): void
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
            ->patchJson(
                "/api/v1/admin/users/{$learner->id}",
                [
                    'name' => 'Nu trebuie modificat',
                ]
            )
            ->assertNotFound();

        $learner->refresh();

        $this->assertNotSame(
            'Nu trebuie modificat',
            $learner->name
        );
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()
            ->teacher()
            ->create([
                'email' => 'inactiv@openedu.ro',
                'password' => 'Parola-Sigura-2026',
                'is_active' => false,
            ]);

        $this
            ->postJson('/api/v1/auth/login', [
                'email' => 'inactiv@openedu.ro',
                'password' => 'Parola-Sigura-2026',
                'device_name' => 'OpenEdu Test',
            ])
            ->assertForbidden()
            ->assertJsonPath(
                'error.code',
                'ACCOUNT_INACTIVE'
            );

        $this->assertDatabaseCount(
            'personal_access_tokens',
            0
        );
    }
}
