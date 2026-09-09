<?php

namespace Tests\Feature;

use App\Livewire\Admin\Applications;
use App\Livewire\Auth\PartnerLogin;
use App\Livewire\Public\ApplyForm;
use App\Models\Partner;
use App\Models\PartnerApplication;
use App\Models\PromoCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\Concerns\SeedsDefaultCountries;
use Tests\TestCase;

class PartnerApplicationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsDefaultCountries;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedDefaultCountries();
    }

    public function test_apply_form_creates_pending_partner_with_password(): void
    {
        Livewire::test(ApplyForm::class)
            ->set('firstName', 'Sardor')
            ->set('lastName', 'Rahimov')
            ->set('email', 'sardor@example.com')
            ->set('phone', '+1234567890')
            ->set('password', 'password123')
            ->set('passwordConfirmation', 'password123')
            ->set('platforms', ['telegram'])
            ->set('telegram', 'https://t.me/sardor')
            ->set('instagram', '')
            ->set('followers', '10k-50k')
            ->set('niche', 'travel')
            ->set('country', 'US')
            ->set('about', 'Test partner application')
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('partner_applications', [
            'email' => 'sardor@example.com',
            'status' => 'pending',
            'telegram' => 'https://t.me/sardor',
            'country' => 'US',
        ]);

        $partner = Partner::query()->where('email', 'sardor@example.com')->first();
        $this->assertNotNull($partner);
        $this->assertTrue($partner->isPending());
        $this->assertTrue(Hash::check('password123', $partner->password));
        $this->assertSame($partner->id, PartnerApplication::query()->where('email', 'sardor@example.com')->value('partner_id'));
    }

    public function test_apply_form_lists_seeded_countries(): void
    {
        Livewire::test(ApplyForm::class)
            ->assertSee('United States')
            ->assertSee('United Kingdom')
            ->assertSee('Japan')
            ->assertSeeHtml('value="US"')
            ->assertDontSee('tajikistan');
    }

    public function test_apply_form_requires_password(): void
    {
        Livewire::test(ApplyForm::class)
            ->set('firstName', 'Sardor')
            ->set('lastName', 'Rahimov')
            ->set('email', 'sardor@example.com')
            ->set('phone', '+1234567890')
            ->set('platforms', ['telegram'])
            ->set('telegram', 'https://t.me/sardor')
            ->set('followers', '10k-50k')
            ->set('niche', 'travel')
            ->call('submit')
            ->assertHasErrors(['password'])
            ->assertSet('submitted', false);

        $this->assertDatabaseCount('partners', 0);
        $this->assertDatabaseCount('partner_applications', 0);
    }

    public function test_pending_applicant_cannot_login_until_approved(): void
    {
        Livewire::test(ApplyForm::class)
            ->set('firstName', 'Sardor')
            ->set('lastName', 'Rahimov')
            ->set('email', 'sardor@example.com')
            ->set('phone', '+1234567890')
            ->set('password', 'password123')
            ->set('passwordConfirmation', 'password123')
            ->set('platforms', ['telegram'])
            ->set('telegram', 'https://t.me/sardor')
            ->set('followers', '10k-50k')
            ->set('niche', 'travel')
            ->call('submit')
            ->assertSet('submitted', true);

        $login = Livewire::test(PartnerLogin::class)
            ->set('email', 'sardor@example.com')
            ->set('password', 'password123')
            ->call('login');

        $login->assertHasErrors(['email']);
        $this->assertSame(__('auth.partner.pending_approval'), $login->errors()->first('email'));
        $this->assertGuest('partner');

        $app = PartnerApplication::query()->where('email', 'sardor@example.com')->first();
        $this->assertNotNull($app);

        Livewire::test(Applications::class)
            ->call('approve', $app->id);

        $this->assertTrue(Partner::query()->where('email', 'sardor@example.com')->first()?->isActive());
        $this->assertSame(1, Partner::query()->where('email', 'sardor@example.com')->count());

        Livewire::test(PartnerLogin::class)
            ->set('email', 'sardor@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect(route('partner.dashboard'));

        $this->assertTrue(Auth::guard('partner')->check());
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

    public function test_reject_blocks_pending_partner(): void
    {
        Livewire::test(ApplyForm::class)
            ->set('firstName', 'Sardor')
            ->set('lastName', 'Rahimov')
            ->set('email', 'sardor-reject@example.com')
            ->set('phone', '+1234567890')
            ->set('password', 'password123')
            ->set('passwordConfirmation', 'password123')
            ->set('platforms', ['telegram'])
            ->set('telegram', 'https://t.me/sardor')
            ->set('followers', '10k-50k')
            ->set('niche', 'travel')
            ->call('submit');

        $app = PartnerApplication::query()->where('email', 'sardor-reject@example.com')->first();
        $this->assertNotNull($app);

        Livewire::test(Applications::class)
            ->call('reject', $app->id);

        $partner = Partner::query()->where('email', 'sardor-reject@example.com')->first();
        $this->assertNotNull($partner);
        $this->assertSame('blocked', $partner->status);
        $this->assertSame('rejected', $app->fresh()->status);
    }
}
