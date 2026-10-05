<?php

namespace Tests\Feature\Api\V1;

use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Tests\TestCase;

class ApiContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/api/v1/_contract/validate', function (Request $request) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'min:3'],
                'email' => ['required', 'email'],
            ]);

            return ApiResponse::success($validated);
        });

        Route::get('/api/v1/_contract/authentication', function () {
            throw new \Illuminate\Auth\AuthenticationException;
        });

        Route::get('/api/v1/_contract/forbidden', function () {
            throw new AuthorizationException;
        });

        Route::get('/api/v1/_contract/rate-limit', function () {
            throw new TooManyRequestsHttpException(60);
        });

        Route::get('/api/v1/_contract/internal-error', function () {
            throw new RuntimeException(
                'Sensitive internal diagnostic information.'
            );
        });
    }

    public function test_validation_errors_follow_openedu_contract(): void
    {
        $response = $this->postJson('/api/v1/_contract/validate', [
            'email' => 'not-an-email',
        ]);

        $response
            ->assertUnprocessable()
            ->assertHeader('X-Request-ID')
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonPath(
                'error.message',
                'Datele trimise nu sunt valide.'
            )
            ->assertJsonStructure([
                'error' => [
                    'code',
                    'message',
                    'details' => [
                        'fields' => [
                            'name',
                            'email',
                        ],
                    ],
                ],
                'meta' => [
                    'request_id',
                ],
            ]);

        $this->assertRequestIdIsConsistent($response);
    }

    public function test_unauthenticated_errors_follow_openedu_contract(): void
    {
        $response = $this->getJson('/api/v1/_contract/authentication');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED')
            ->assertJsonPath(
                'error.message',
                'Autentificarea este necesară.'
            );

        $this->assertRequestIdIsConsistent($response);
    }

    public function test_forbidden_errors_follow_openedu_contract(): void
    {
        $response = $this->getJson('/api/v1/_contract/forbidden');

        $response
            ->assertForbidden()
            ->assertJsonPath('error.code', 'FORBIDDEN')
            ->assertJsonPath(
                'error.message',
                'Nu aveți permisiunea necesară pentru această acțiune.'
            );

        $this->assertRequestIdIsConsistent($response);
    }

    public function test_not_found_errors_follow_openedu_contract(): void
    {
        $response = $this->getJson('/api/v1/_contract/this-does-not-exist');

        $response
            ->assertNotFound()
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND')
            ->assertJsonPath(
                'error.message',
                'Resursa solicitată nu a fost găsită.'
            );

        $this->assertRequestIdIsConsistent($response);
    }

    public function test_rate_limit_errors_follow_openedu_contract(): void
    {
        $response = $this->getJson('/api/v1/_contract/rate-limit');

        $response
            ->assertStatus(429)
            ->assertHeader('Retry-After', '60')
            ->assertJsonPath('error.code', 'RATE_LIMIT_EXCEEDED')
            ->assertJsonPath(
                'error.message',
                'Au fost trimise prea multe cereri. Încercați din nou mai târziu.'
            );

        $this->assertRequestIdIsConsistent($response);
    }

    public function test_internal_errors_follow_openedu_contract_without_leaking_details(): void
    {
        $response = $this->getJson('/api/v1/_contract/internal-error');

        $response
            ->assertInternalServerError()
            ->assertJsonPath('error.code', 'INTERNAL_ERROR')
            ->assertJsonPath(
                'error.message',
                'A apărut o eroare internă.'
            );

        $this->assertRequestIdIsConsistent($response);

        $payload = $response->json();

        $this->assertArrayNotHasKey('exception', $payload);
        $this->assertArrayNotHasKey('file', $payload);
        $this->assertArrayNotHasKey('line', $payload);
        $this->assertArrayNotHasKey('trace', $payload);

        $this->assertStringNotContainsString(
            'Sensitive internal diagnostic information.',
            $response->getContent()
        );
    }

    private function assertRequestIdIsConsistent($response): void
    {
        $response->assertHeader('X-Request-ID');

        $requestId = $response->headers->get('X-Request-ID');

        $this->assertNotNull($requestId);

        $response->assertJsonPath('meta.request_id', $requestId);
    }
}
