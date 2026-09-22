<?php

namespace Tests\Feature;

use App\Livewire\Admin\LegalPages;
use App\Livewire\Admin\Settings;
use App\Models\Admin;
use App\Models\ContentBlock;
use Database\Seeders\TranslatableContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LegalContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TranslatableContentSeeder::class);
    }

    public function test_public_legal_pages_render_seeded_content(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('ZoodeSIM')
            ->assertSee('Information we collect')
            ->assertSee('We do not sell your personal information');

        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSee('Terms & Conditions')
            ->assertSee('Acceptable use');

        $this->get(route('legal.delete-account'))
            ->assertOk()
            ->assertSee('Delete account')
            ->assertSee('How to delete')
            ->assertSee(__('ui.privacy'));
    }

    public function test_public_legal_page_returns_404_when_inactive(): void
    {
        ContentBlock::query()->where('slug', ContentBlock::LEGAL_PRIVACY)->update(['is_active' => false]);

        $this->get(route('legal.privacy'))->assertNotFound();
    }

    public function test_legal_api_returns_localized_copy_without_auth(): void
    {
        $this->getJson('/api/user/legal/privacy?lang=es')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.page', 'privacy')
            ->assertJsonPath('data.slug', ContentBlock::LEGAL_PRIVACY)
            ->assertJsonPath('data.title', 'Política de privacidad');

        $this->getJson('/api/user/legal/terms')
            ->assertOk()
            ->assertJsonPath('data.page', 'terms');

        $this->getJson('/api/user/legal/delete-account')
            ->assertOk()
            ->assertJsonPath('data.page', 'delete-account');
    }

    public function test_legal_api_returns_404_when_inactive(): void
    {
        ContentBlock::query()->where('slug', ContentBlock::LEGAL_TERMS)->update(['is_active' => false]);

        $this->getJson('/api/user/legal/terms')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('api.legal.not_found'));
    }

    public function test_admin_can_update_legal_page_translation(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Legal Admin',
            'email' => 'legal-admin@zoodesim.test',
            'password' => 'password123',
        ]);

        $block = ContentBlock::query()->where('slug', ContentBlock::LEGAL_PRIVACY)->firstOrFail();

        $this->actingAs($admin, 'admin');

        Livewire::test(LegalPages::class)
            ->call('openEditor', $block->id)
            ->set('editLocale', 'en')
            ->set('blockTitle', 'Updated Privacy')
            ->set('blockBody', 'Updated privacy body for tests.')
            ->set('blockIsActive', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $block->refresh();
        $this->assertSame('Updated Privacy', $block->getTranslation('title', 'en'));
        $this->assertSame('Updated privacy body for tests.', $block->getTranslation('body', 'en'));
    }

    public function test_settings_does_not_list_legal_blocks(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Settings Admin',
            'email' => 'settings-legal@zoodesim.test',
            'password' => 'password123',
        ]);

        $this->actingAs($admin, 'admin');

        Livewire::test(Settings::class)
            ->assertSee('apply.hero.title')
            ->assertDontSee('legal.privacy')
            ->assertDontSee('legal.terms')
            ->assertDontSee('legal.delete_account');
    }
}
