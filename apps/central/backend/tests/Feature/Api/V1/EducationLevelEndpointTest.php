<?php

namespace Tests\Feature\Api\V1;

use App\Models\EducationLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EducationLevelEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_education_levels_in_ordinal_order(): void
    {
        EducationLevel::create([
            'code' => 'grade-7',
            'name' => 'Clasa a VII-a',
            'ordinal' => 7,
            'education_stage' => 'gimnazial',
        ]);

        EducationLevel::create([
            'code' => 'grade-5',
            'name' => 'Clasa a V-a',
            'ordinal' => 5,
            'education_stage' => 'gimnazial',
        ]);

        $response = $this->getJson('/api/v1/education-levels');

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.code', 'grade-5')
            ->assertJsonPath('data.0.name', 'Clasa a V-a')
            ->assertJsonPath('data.0.ordinal', 5)
            ->assertJsonPath('data.1.code', 'grade-7')
            ->assertJsonPath('data.1.name', 'Clasa a VII-a')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                        'ordinal',
                        'education_stage',
                    ],
                ],
                'meta' => [
                    'request_id',
                ],
            ]);

        $requestId = $response->headers->get('X-Request-ID');

        $response->assertJsonPath(
            'meta.request_id',
            $requestId
        );
    }
}
