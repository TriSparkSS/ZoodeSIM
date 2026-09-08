<?php

namespace Tests\Feature;

use App\Livewire\Admin\Applications;
use App\Livewire\Public\ApplyForm;
use App\Models\Partner;
use App\Models\PartnerApplication;
use App\Models\PromoCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_form_creates_partner_application(): void
    {
        Livewire::test(ApplyForm::class)
            ->set('firstName', 'Sardor')
            ->set('lastName', 'Rahimov')
            ->set('email', 'sardor@example.com')
            ->set('phone', '+1234567890')
            ->set('platforms', ['telegram'])
            ->set('telegram', 'https://t.me/sardor')
            ->set('instagram', '')
            ->set('followers', '10k-50k')
            ->set('niche', 'travel')
            ->set('country', 'tajikistan')
            ->set('about', 'Test partner application')
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('partner_applications', [
            'email' => 'sardor@example.com',
            'status' => 'pending',
            'telegram' => 'https://t.me/sardor',
        ]);
    }

    public function test_admin_approve_creates_partner_and_promo_code(): void
    {
        $app = PartnerApplication::query()->create([
            'first_name' => 'Sardor',
            'last_name' => 'Rahimov',
            'email' => 'sardor2@example.com',
            'phone' => '+1234567890',
            'platforms' => ['telegram', 'instagram'],
            'telegram' => 'https://t.me/sardor2',
            'instagram' => 'https://instagram.com/sardor2',
            'tiktok' => null,
            'youtube' => null,
            'followers' => '10k-50k',
            'niche' => 'travel',
            'country' => 'tajikistan',
            'about' => 'Test approved partner',
            'status' => 'pending',
        ]);

        Livewire::test(Applications::class)
            ->call('approve', $app->id);

        $partner = Partner::query()->where('email', 'sardor2@example.com')->first();
        $this->assertNotNull($partner);

        $this->assertSame('https://t.me/sardor2', $partner->social_contacts['telegram'] ?? null);
        $this->assertSame('https://instagram.com/sardor2', $partner->social_contacts['instagram'] ?? null);

        $promo = PromoCode::query()
            ->where('partner_id', $partner->id)
            ->where('code', 'SARDOR10')
            ->first();

        $this->assertNotNull($promo);
        $this->assertSame(200, $promo->bonus_mb);
        $this->assertSame('1.50', (string) $promo->partner_reward);
    }
}
