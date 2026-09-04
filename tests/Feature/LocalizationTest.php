<?php

namespace Tests\Feature;

use App\Models\ContentBlock;
use App\Models\ProgramSetting;
use App\Services\Content\ContentBlockService;
use App\Services\Locale\LocaleManager;
use Database\Seeders\TranslatableContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_switch_route_sets_session_locale(): void
    {
        $response = $this->from('/apply')->get(route('locale.switch', ['locale' => 'ru']));

        $response->assertRedirect('/apply');
        $this->assertSame('ru', session('locale'));
    }

    public function test_unsupported_locale_falls_back_to_english(): void
    {
        $manager = app(LocaleManager::class);

        $this->assertSame('en', $manager->resolve('xx'));
        $this->assertFalse($manager->isSupported('ar'));
        $this->assertTrue($manager->isSupported('tg'));
    }

    public function test_content_block_returns_translation_for_current_locale(): void
    {
        $this->seed(TranslatableContentSeeder::class);

        app()->setLocale('es');

        $title = app(ContentBlockService::class)->title('apply.hero.title');

        $this->assertStringContainsString('ZoodeSIM', $title);
        $this->assertNotSame('', $title);

        $block = ContentBlock::query()->where('slug', 'apply.hero.title')->first();
        $this->assertNotNull($block);
        $this->assertSame(
            $block->getTranslation('title', 'es'),
            $title
        );
    }

    public function test_content_block_falls_back_to_english_when_translation_missing(): void
    {
        $block = new ContentBlock([
            'slug' => 'test.fallback',
            'is_active' => true,
        ]);
        $block->setTranslation('title', 'en', 'English only title');
        $block->setTranslation('body', 'en', 'English body');
        $block->save();

        app()->setLocale('tg');

        $this->assertSame(
            'English only title',
            app(ContentBlockService::class)->title('test.fallback')
        );
    }

    public function test_program_settings_expose_localized_labels(): void
    {
        $this->seed(TranslatableContentSeeder::class);

        app()->setLocale('de');

        $setting = ProgramSetting::query()->where('key', 'registration_reward')->first();

        $this->assertNotNull($setting);
        $this->assertSame('Registrierungsprämie', $setting->label);
    }

    public function test_static_ui_translations_exist_for_supported_locales(): void
    {
        foreach (['en', 'es', 'ru', 'fr', 'de', 'tg'] as $locale) {
            app()->setLocale($locale);
            $this->assertNotSame('ui.save', __('ui.save'));
            $this->assertNotSame('admin.nav.promo_codes', __('admin.nav.promo_codes'));
            $this->assertNotSame('admin.promo_codes.create', __('admin.promo_codes.create'));
        }
    }
}
