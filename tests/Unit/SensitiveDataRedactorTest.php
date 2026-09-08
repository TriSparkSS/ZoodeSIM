<?php

namespace Tests\Unit;

use App\Services\Logging\SensitiveDataRedactor;
use Tests\TestCase;

class SensitiveDataRedactorTest extends TestCase
{
    public function test_it_masks_authorization_as_bearer_placeholder(): void
    {
        $redactor = app(SensitiveDataRedactor::class);

        $headers = $redactor->redactHeaders([
            'Authorization' => 'Bearer super-secret-token',
            'X-API-Key' => 'test-api-key',
            'X-API-Secret' => 'test-api-secret',
            'Accept' => 'application/json',
        ]);

        $this->assertSame('Bearer ********', $headers['Authorization']);
        $this->assertSame('********', $headers['X-API-Key']);
        $this->assertSame('********', $headers['X-API-Secret']);
        $this->assertSame('application/json', $headers['Accept']);
    }

    public function test_it_redacts_nested_json_and_arrays(): void
    {
        $redactor = app(SensitiveDataRedactor::class);

        $redacted = $redactor->redact([
            'email' => 'user@example.com',
            'password' => 'secret-pass',
            'payment' => [
                'card_number' => '4111111111111111',
                'cvv' => '123',
                'amount' => 10,
            ],
            'tokens' => [
                ['access_token' => 'abc', 'refresh_token' => 'def'],
            ],
        ]);

        $this->assertSame('user@example.com', $redacted['email']);
        $this->assertSame('********', $redacted['password']);
        $this->assertSame('********', $redacted['payment']['card_number']);
        $this->assertSame('********', $redacted['payment']['cvv']);
        $this->assertSame(10, $redacted['payment']['amount']);
        $this->assertSame('********', $redacted['tokens'][0]['access_token']);
        $this->assertSame('********', $redacted['tokens'][0]['refresh_token']);
    }

    public function test_it_scrubs_known_secrets_from_strings(): void
    {
        $redactor = app(SensitiveDataRedactor::class);

        $this->assertSame(
            'key=******** secret=********',
            $redactor->redactString('key=test-api-key secret=test-api-secret'),
        );
    }
}
