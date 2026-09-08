<?php

namespace Database\Factories;

use App\Models\ApiLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiLog>
 */
class ApiLogFactory extends Factory
{
    protected $model = ApiLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => ApiLog::TYPE_INTERNAL,
            'service' => ApiLog::SERVICE_PORTAL,
            'method' => 'GET',
            'endpoint' => '/api/user/esim/packages',
            'full_url' => 'https://esim.test/api/user/esim/packages',
            'request_headers' => ['Accept' => 'application/json'],
            'request_body' => [],
            'response_status' => 200,
            'response_headers' => ['Content-Type' => 'application/json'],
            'response_body' => ['success' => true],
            'response_time_ms' => 12,
            'ip_address' => '127.0.0.1',
        ];
    }
}
