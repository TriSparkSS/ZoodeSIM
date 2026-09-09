<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceFraudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeResellPortalClientCreate();
    }

    public function test_referral_registration_requires_device_id(): void
    {
        $this->makePromo($this->makePartner());

        $this->postJson('/api/user/register', $this->payload([
            'referral_code' => 'VALID10',
            'device_id' => null,
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('message', __('api.promo.device_required'));
    }

    public function test_same_device_cannot_redeem_a_second_promo(): void
    {
        $this->makePromo($this->makePartner());

        $this->postJson('/api/user/register', $this->payload([
            'referral_code' => 'VALID10',
            'device_id' => 'shared-phone-01',
        ]))->assertCreated();

        $this->postJson('/api/user/register', $this->payload([
            'name' => 'Second User',
            'email' => 'second@example.com',
            'phone' => '+15550000002',
            'referral_code' => 'VALID10',
            'device_id' => 'shared-phone-01',
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('message', __('api.promo.device_already_used'));

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('promo_usage', 1);
    }

    public function test_blocked_ip_cannot_register(): void
    {
        config(['fraud.blocked_ips' => ['127.0.0.1']]);

        $this->postJson('/api/user/register', $this->payload())
            ->assertUnprocessable()
            ->assertJsonPath('message', __('api.promo.ip_blocked'));
    }

    public function test_ip_velocity_limit_blocks_extra_registrations(): void
    {
        config(['fraud.max_registrations_per_ip' => 2]);

        $this->postJson('/api/user/register', $this->payload())->assertCreated();
        $this->postJson('/api/user/register', $this->payload([
            'email' => 'two@example.com',
            'phone' => '+15550000002',
        ]))->assertCreated();

        $this->postJson('/api/user/register', $this->payload([
            'email' => 'three@example.com',
            'phone' => '+15550000003',
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('message', __('api.promo.ip_limited'));
    }

    public function test_registration_stores_device_and_ip(): void
    {
        $this->makePromo($this->makePartner());

        $this->postJson('/api/user/register', $this->payload([
            'referral_code' => 'VALID10',
            'device_id' => 'iphone-abc-001',
        ]))->assertCreated();

        $user = User::query()->where('email', 'user@example.com')->first();
        $this->assertSame('iphone-abc-001', $user->device_id);
        $this->assertNotEmpty($user->registration_ip);
        $this->assertDatabaseHas('promo_usage', [
            'user_id' => $user->id,
            'device_id' => 'iphone-abc-001',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ada Lovelace',
            'email' => 'user@example.com',
            'phone' => '+1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    protected function makePartner(): Partner
    {
        return Partner::query()->create([
            'name' => 'Fraud Partner',
            'email' => 'fraud-partner@example.com',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);
    }

    protected function makePromo(Partner $partner): PromoCode
    {
        return PromoCode::query()->create([
            'partner_id' => $partner->id,
            'code' => 'VALID10',
            'bonus_mb' => 200,
            'partner_reward' => 1.50,
            'type' => 'standard',
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'usage_count' => 0,
            'max_usage' => null,
        ]);
    }
}
