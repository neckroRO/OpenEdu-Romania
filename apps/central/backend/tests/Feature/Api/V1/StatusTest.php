<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;

class StatusTest extends TestCase
{
    public function test_status_endpoint_is_available(): void
    {
        $response = $this->getJson('/api/v1/status');

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonPath('data.service', 'openedu-central-api')
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.api_version', 'v1')
            ->assertJsonStructure([
                'data' => [
                    'service',
                    'status',
                    'api_version',
                ],
                'meta' => [
                    'request_id',
                ],
            ]);

        $requestId = $response->headers->get('X-Request-ID');

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $requestId
        );

        $response->assertJsonPath('meta.request_id', $requestId);
    }
}
