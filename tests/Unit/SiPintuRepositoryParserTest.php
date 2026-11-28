<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\SiPintuApiException;
use App\Repositories\Interfaces\SiPintuRepositoryInterface;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SiPintuRepositoryParserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.sipintu.api_url' => 'https://sipintu.test',
            'services.sipintu.client_id' => 'unit_client_id',
            'services.sipintu.client_secret' => 'unit_client_secret',
            'services.sipintu.timeout' => 5,
            'services.sipintu.per_page' => null,
        ]);
    }

    public function test_unifies_dudi_path_to_sijuna_dudi_without_fallback_mismatch(): void
    {
        Http::fake([
            'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response(['data' => [['id' => 'D1', 'nama_perusahaan' => 'PT Resmi']]], 200),
            'https://sipintu.test/api/v1/dudi*' => Http::response(['message' => 'Should not hit legacy /api/v1/dudi'], 500),
        ]);

        /** @var SiPintuRepositoryInterface $repo */
        $repo = app(SiPintuRepositoryInterface::class);
        $records = $repo->fetchDudi();

        $this->assertCount(1, $records);
        $this->assertSame('D1', $records[0]['id']);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/api/v1/sijuna/dudi'));
        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/api/v1/dudi'));
    }

    public function test_parse_response_rejects_error_payloads_and_unknown_envelopes(): void
    {
        Http::fake([
            'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response([
                'status' => 'error',
                'message' => 'Gateway maintenance',
            ], 200),
        ]);

        /** @var SiPintuRepositoryInterface $repo */
        $repo = app(SiPintuRepositoryInterface::class);

        $this->expectException(SiPintuApiException::class);
        $repo->fetchDudi();
    }

    public function test_check_endpoint_status_validates_payload_structure_and_extracts_count_and_http_status(): void
    {
        Http::fake([
            'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response([
                'data' => [
                    'items' => [
                        ['id' => 'D1', 'nama_perusahaan' => 'PT Satu'],
                        ['id' => 'D2', 'nama_perusahaan' => 'PT Dua'],
                    ],
                ],
                'meta' => ['total' => 42],
            ], 200),
            'https://sipintu.test/api/v1/sijuna/teachers*' => Http::response([
                'unexpected_key' => 'not a valid payload',
            ], 200),
        ]);

        /** @var SiPintuRepositoryInterface $repo */
        $repo = app(SiPintuRepositoryInterface::class);

        $dudiResult = $repo->checkEndpointStatus(SiPintuRepositoryInterface::ENDPOINT_DUDI);
        $this->assertTrue($dudiResult['success']);
        $this->assertSame(200, $dudiResult['status']);
        $this->assertSame(200, $dudiResult['http_status']);
        $this->assertSame(42, $dudiResult['count']);

        $teacherResult = $repo->checkEndpointStatus(SiPintuRepositoryInterface::ENDPOINT_TEACHERS);
        $this->assertFalse($teacherResult['success']);
        $this->assertSame(200, $teacherResult['status']);
        $this->assertSame(200, $teacherResult['http_status']);
        $this->assertNull($teacherResult['count']);
    }
}
