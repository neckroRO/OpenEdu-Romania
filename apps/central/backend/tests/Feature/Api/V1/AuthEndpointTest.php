<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_sanctum_token(): void
    {
        $user = User::factory()
            ->teacher()
            ->create([
                'email' => 'profesor@openedu.ro',
                'password' => 'secret-password',
            ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'profesor@openedu.ro',
            'password' => 'secret-password',
            'device_name' => 'OpenEdu Test',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath(
                'data.user.email',
                'profesor@openedu.ro'
            )
            ->assertJsonPath(
                'data.user.role',
                UserRole::Teacher->value
            )
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                    'expires_at',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                    ],
                ],
                'meta' => [
                    'request_id',
                ],
            ]);

        $this->assertDatabaseCount(
            'personal_access_tokens',
            1
        );

        $this->assertNotEmpty(
            $response->json('data.token')
        );
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'profesor@openedu.ro',
            'password' => 'correct-password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'profesor@openedu.ro',
            'password' => 'wrong-password',
            'device_name' => 'OpenEdu Test',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath(
                'error.code',
                'INVALID_CREDENTIALS'
            )
            ->assertJsonPath(
                'error.message',
                'Datele de autentificare nu sunt corecte.'
            );

        $this->assertDatabaseCount(
            'personal_access_tokens',
            0
        );
    }

    public function test_me_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response
            ->assertUnauthorized()
            ->assertJsonPath(
                'error.code',
                'UNAUTHENTICATED'
            );
    }

    public function test_authenticated_user_can_view_identity(): void
    {
        $user = User::factory()
            ->moderator()
            ->create();

        $token = $user
            ->createToken('OpenEdu Test')
            ->plainTextToken;

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/auth/me');

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $user->id
            )
            ->assertJsonPath(
                'data.email',
                $user->email
            )
            ->assertJsonPath(
                'data.role',
                UserRole::Moderator->value
            );
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()
            ->teacher()
            ->create();

        $token = $user
            ->createToken('OpenEdu Test')
            ->plainTextToken;

        $this->assertDatabaseCount(
            'personal_access_tokens',
            1
        );

        $response = $this
            ->withToken($token)
            ->postJson('/api/v1/auth/logout');

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.message',
                'Deconectarea a fost efectuată cu succes.'
            );

        $this->assertDatabaseCount(
            'personal_access_tokens',
            0
        );

        Auth::forgetGuards();

        $this
            ->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath(
                'error.code',
                'UNAUTHENTICATED'
            );
    }
}
