<?php

namespace Tests\Unit;

use App\Support\ResellPortalProviderId;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ResellPortalProviderIdTest extends TestCase
{
    #[DataProvider('validIds')]
    public function test_it_normalizes_provider_ids(mixed $value, string $expected): void
    {
        $this->assertSame($expected, ResellPortalProviderId::from($value));
    }

    /**
     * @return array<string, array{0: mixed, 1: string}>
     */
    public static function validIds(): array
    {
        return [
            'integer' => [123, '123'],
            'numeric string' => ['123', '123'],
            'test client' => ['test_cli_123', 'test_cli_123'],
            'test service' => ['test_svc_789', 'test_svc_789'],
        ];
    }

    #[DataProvider('invalidIds')]
    public function test_it_rejects_empty_ids(mixed $value): void
    {
        $this->assertNull(ResellPortalProviderId::from($value));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidIds(): array
    {
        return [
            'null' => [null],
            'zero' => [0],
            'zero string' => ['0'],
            'empty' => [''],
            'blank' => ['  '],
        ];
    }
}
