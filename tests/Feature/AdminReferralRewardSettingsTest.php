<?php

namespace Tests\Feature;

use App\Livewire\Admin\Settings;
use App\Models\Admin;
use App\Models\ProgramSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserReferral;
use Database\Seeders\TranslatableContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminReferralRewardSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeResellPortalClientCreate();
        $this->seed(TranslatableContentSeeder::class);
    }

    public function test_admin_referral_rewards_credit_both_wallets_and_transactions(): void
    {
        ProgramSetting::query()->where('key', 'user_referral_referrer_reward')->update(['value' => '9.00']);
        ProgramSetting::query()->where('key', 'user_referral_invitee_reward')->update(['value' => '8.00']);

        $admin = Admin::query()->create([
            'name' => 'Reward Admin',
            'email' => 'reward-admin@zoodesim.test',
            'password' => 'password123',
        ]);

        $this->actingAs($admin, 'admin');

        Livewire::test(Settings::class)
            ->assertSee('User referral referrer reward')
            ->assertSee('User referral invitee reward')
            ->assertDontSee('Registration reward')
            ->assertDontSee('Purchase commission')
            ->assertDontSee('Subsequent purchase commission')
            ->assertDontSee('Default user bonus')
            ->assertDontSee('Minimum withdrawal')
            ->assertDontSee('First purchase discount')
            ->assertDontSee('Cashback percent')
            ->assertDontSee('10-referral milestone')
            ->set('settingValues.user_referral_referrer_reward', '1.00')
            ->set('settingValues.user_referral_invitee_reward', '1.50')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('1.00', ProgramSetting::query()->where('key', 'user_referral_referrer_reward')->value('value'));
        $this->assertSame('1.50', ProgramSetting::query()->where('key', 'user_referral_invitee_reward')->value('value'));
        $this->assertNotNull(ProgramSetting::query()->where('key', 'registration_reward')->value('value'));

        $referrer = User::factory()->create([
            'name' => 'Referrer User',
            'email' => 'referrer@example.com',
            'phone' => '+15551111901',
        ]);

        $this->postJson('/api/user/register', [
            'name' => 'Ada Lovelace',
            'email' => 'user@example.com',
            'phone' => '+1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_id' => 'reward-settings-device',
            'referral_code' => $referrer->referral_code,
        ])->assertCreated();

        $invitee = User::query()->where('email', 'user@example.com')->firstOrFail();
        $referral = UserReferral::query()->where('referred_id', $invitee->id)->firstOrFail();

        $this->assertEquals(1.00, (float) $referrer->fresh()->balance);
        $this->assertEquals(1.50, (float) $invitee->balance);

        $reward = Transaction::query()
            ->where('transactable_id', $referrer->id)
            ->where('category', Transaction::CATEGORY_USER_REFERRAL_REWARD)
            ->firstOrFail();
        $bonus = Transaction::query()
            ->where('transactable_id', $invitee->id)
            ->where('category', Transaction::CATEGORY_USER_REFERRAL_BONUS)
            ->firstOrFail();

        $this->assertSame(Transaction::TYPE_CREDIT, $reward->type);
        $this->assertSame('1.00', (string) $reward->amount);
        $this->assertSame('USD', $reward->currency);
        $this->assertSame(User::class, $reward->transactable_type);
        $this->assertSame('user_referral', $reward->reference_type);
        $this->assertSame($referral->id, $reward->reference_id);
        $this->assertSame($invitee->id, $reward->meta['referred_id']);
        $this->assertSame($referrer->referral_code, $reward->meta['referral_code']);

        $this->assertSame(Transaction::TYPE_CREDIT, $bonus->type);
        $this->assertSame('1.50', (string) $bonus->amount);
        $this->assertSame('USD', $bonus->currency);
        $this->assertSame(User::class, $bonus->transactable_type);
        $this->assertSame('user_referral', $bonus->reference_type);
        $this->assertSame($referral->id, $bonus->reference_id);
        $this->assertSame($referrer->id, $bonus->meta['referrer_id']);
        $this->assertSame($referrer->referral_code, $bonus->meta['referral_code']);
    }
}
