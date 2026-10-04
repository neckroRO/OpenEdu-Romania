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
            ->assertExactJson([
                'data' => [
                    'service' => 'openedu-central-api',
                    'status' => 'ok',
                    'api_version' => 'v1',
                ],
            ]);

        $requestId = $response->headers->get('X-Request-ID');

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $requestId
        );
    }
}
