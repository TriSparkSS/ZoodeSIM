<?php

namespace Tests\Feature;

use App\Livewire\Admin\Banners;
use App\Models\Admin;
use App\Models\Banner;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

class BannerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Banner Admin',
            'email' => 'banner-admin@zoodesim.test',
            'password' => 'password123',
        ]);
    }

    protected function partner(): Partner
    {
        return Partner::query()->create([
            'name' => 'Banner Partner',
            'email' => 'banner-partner@zoodesim.test',
            'password' => 'password123',
            'social_contacts' => ['telegram' => null, 'instagram' => null, 'twitter' => null],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);
    }

    public function test_admin_can_create_banner_for_user_app(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin(), 'admin');

        Livewire::test(Banners::class)
            ->call('openCreateModal')
            ->set('formTitle', 'Summer sale')
            ->set('formDisplayOn', Banner::DISPLAY_USER_APP)
            ->set('formLinkUrl', 'https://example.com/sale')
            ->set('formSortOrder', '2')
            ->set('formIsActive', true)
            ->set('formImage', UploadedFile::fake()->image('promo.jpg', 800, 300))
            ->call('save')
            ->assertHasNoErrors();

        $banner = Banner::query()->where('title', 'Summer sale')->first();
        $this->assertNotNull($banner);
        $this->assertSame(Banner::DISPLAY_USER_APP, $banner->display_on);
        $this->assertSame('https://example.com/sale', $banner->link_url);
        $this->assertTrue($banner->is_active);
        $this->assertSame(2, $banner->sort_order);
        $this->assertTrue(str_starts_with((string) $banner->image_path, 'banners/'));
        Storage::disk('public')->assertExists($banner->image_path);
    }

    public function test_create_requires_image_and_display_on(): void
    {
        $this->actingAs($this->admin(), 'admin');

        Livewire::test(Banners::class)
            ->call('openCreateModal')
            ->set('formTitle', 'No image')
            ->set('formDisplayOn', 'website')
            ->call('save')
            ->assertHasErrors(['formImage', 'formDisplayOn']);

        $this->assertDatabaseCount('banners', 0);
    }

    public function test_admin_can_update_toggle_and_delete_banner(): void
    {
        Storage::fake('public');
        $banner = $this->makeBanner(Banner::DISPLAY_BOTH, true, 'Keep me');

        $this->actingAs($this->admin(), 'admin');

        Livewire::test(Banners::class)
            ->call('openEditModal', $banner->id)
            ->set('formTitle', 'Updated banner')
            ->set('formDisplayOn', Banner::DISPLAY_PARTNER_PANEL)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Updated banner', $banner->fresh()->title);
        $this->assertSame(Banner::DISPLAY_PARTNER_PANEL, $banner->fresh()->display_on);

        Livewire::test(Banners::class)->call('toggle', $banner->id);
        $this->assertFalse($banner->fresh()->is_active);

        Livewire::test(Banners::class)->call('delete', $banner->id);
        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
        Storage::disk('public')->assertMissing($banner->image_path);
    }

    public function test_user_api_returns_only_active_user_app_banners(): void
    {
        Storage::fake('public');

        $userBanner = $this->makeBanner(Banner::DISPLAY_USER_APP, true, 'App only');
        $bothBanner = $this->makeBanner(Banner::DISPLAY_BOTH, true, 'Both');
        $this->makeBanner(Banner::DISPLAY_PARTNER_PANEL, true, 'Partner only');
        $this->makeBanner(Banner::DISPLAY_USER_APP, false, 'Inactive app');

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/user/banners')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', __('api.banners.retrieved'))
            ->assertJsonCount(2, 'data.banners')
            ->assertJsonFragment(['id' => $userBanner->id, 'title' => 'App only'])
            ->assertJsonFragment(['id' => $bothBanner->id, 'title' => 'Both'])
            ->assertJsonMissing(['title' => 'Partner only'])
            ->assertJsonMissing(['title' => 'Inactive app']);
    }

    public function test_unauthenticated_user_cannot_list_banners(): void
    {
        $this->getJson('/api/user/banners')->assertUnauthorized();
    }

    public function test_partner_dashboard_shows_partner_banners_and_hides_user_only(): void
    {
        Storage::fake('public');
        $this->makeBanner(Banner::DISPLAY_PARTNER_PANEL, true, 'Partner promo');
        $this->makeBanner(Banner::DISPLAY_USER_APP, true, 'App secret');

        $this->actingAs($this->partner(), 'partner')
            ->get(route('partner.dashboard'))
            ->assertOk()
            ->assertSee('Partner promo')
            ->assertDontSee('App secret');
    }

    public function test_guest_cannot_open_admin_banners_page(): void
    {
        $this->get(route('admin.banners'))->assertRedirect(route('admin.login'));
    }

    protected function makeBanner(string $displayOn, bool $active, string $title): Banner
    {
        $path = UploadedFile::fake()->image(str_replace(' ', '-', $title).'.jpg', 640, 240)
            ->store('banners', 'public');

        return Banner::query()->create([
            'title' => $title,
            'image_path' => $path,
            'link_url' => null,
            'display_on' => $displayOn,
            'is_active' => $active,
            'sort_order' => 0,
        ]);
    }
}
