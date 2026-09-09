<?php

namespace Tests\Feature;

use App\Livewire\Admin\Countries;
use App\Models\Admin;
use App\Models\Country;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCountryTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Country Admin',
            'email' => 'country-admin@zoodesim.test',
            'password' => 'password123',
        ]);
    }

    public function test_admin_can_create_a_country(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin(), 'admin');

        Livewire::test(Countries::class)
            ->set('formCode', 'fr')
            ->set('formName', 'France')
            ->set('formSortOrder', '15')
            ->set('formIsActive', true)
            ->set('formImage', UploadedFile::fake()->image('flag.png', 60, 40))
            ->call('save')
            ->assertHasNoErrors();

        $country = Country::query()->where('code', 'FR')->first();
        $this->assertNotNull($country);
        $this->assertSame('France', $country->name);
        $this->assertTrue($country->is_active);
        $this->assertSame(15, $country->sort_order);
        $this->assertNotNull($country->image_path);
        $this->assertTrue(str_starts_with((string) $country->image_path, 'countries/'));
        Storage::disk('public')->assertExists($country->image_path);
    }

    public function test_country_code_must_be_unique(): void
    {
        Country::query()->create([
            'code' => 'IT',
            'name' => 'Italy',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($this->admin(), 'admin');

        Livewire::test(Countries::class)
            ->set('formCode', 'IT')
            ->set('formName', 'Italia')
            ->set('formSortOrder', '1')
            ->call('save')
            ->assertHasErrors(['formCode']);

        $this->assertSame(1, Country::query()->where('code', 'IT')->count());
    }

    public function test_admin_can_update_and_delete_a_country(): void
    {
        $country = Country::query()->create([
            'code' => 'ES',
            'name' => 'Spain',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        $this->actingAs($this->admin(), 'admin');

        Livewire::test(Countries::class)
            ->call('openEditModal', $country->id)
            ->set('formName', 'España')
            ->set('formIsActive', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('España', $country->fresh()->name);
        $this->assertFalse($country->fresh()->is_active);

        Livewire::test(Countries::class)
            ->call('delete', $country->id);

        $this->assertDatabaseMissing('countries', ['id' => $country->id]);
    }

    public function test_country_seeder_inserts_seven_destinations(): void
    {
        $this->seed(CountrySeeder::class);

        $this->assertSame(7, Country::query()->count());
        $this->assertEqualsCanonicalizing(
            ['US', 'GB', 'DE', 'TR', 'AE', 'TH', 'JP'],
            Country::query()->pluck('code')->all(),
        );
    }

    public function test_guest_cannot_open_admin_countries_page(): void
    {
        $this->get(route('admin.countries'))->assertRedirect(route('admin.login'));
    }

    public function test_authenticated_user_can_list_active_countries(): void
    {
        $this->seed(CountrySeeder::class);
        Country::query()->where('code', 'JP')->update(['is_active' => false]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/user/esim/countries')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(6, 'data.countries')
            ->assertJsonFragment(['code' => 'US', 'name' => 'United States'])
            ->assertJsonMissing(['code' => 'JP']);
    }

    public function test_unauthenticated_user_cannot_list_countries(): void
    {
        $this->getJson('/api/user/esim/countries')->assertUnauthorized();
    }
}
