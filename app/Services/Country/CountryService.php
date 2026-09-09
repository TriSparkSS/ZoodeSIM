<?php

namespace App\Services\Country;

use App\Models\Country;
use App\Services\Country\Contracts\CountryServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CountryService implements CountryServiceInterface
{
    /**
     * @return Collection<int, Country>
     */
    public function list(): Collection
    {
        return Country::query()->ordered()->get();
    }

    /**
     * @return Collection<int, Country>
     */
    public function active(): Collection
    {
        return Country::query()->active()->ordered()->get();
    }

    /**
     * @param  array{code: string, name: string, is_active?: bool, sort_order?: int}  $data
     */
    public function create(array $data, ?UploadedFile $image = null): Country
    {
        $code = $this->normalizeCode($data['code']);
        $this->assertCodeIsAvailable($code);

        return Country::query()->create([
            'code' => $code,
            'name' => trim($data['name']),
            'image_path' => $image ? $this->storeImage($image) : null,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
    }

    /**
     * @param  array{code?: string, name?: string, is_active?: bool, sort_order?: int}  $data
     */
    public function update(Country $country, array $data, ?UploadedFile $image = null): Country
    {
        $payload = [];

        if (array_key_exists('code', $data)) {
            $code = $this->normalizeCode($data['code']);
            $this->assertCodeIsAvailable($code, $country->id);
            $payload['code'] = $code;
        }

        if (array_key_exists('name', $data)) {
            $payload['name'] = trim($data['name']);
        }

        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = (bool) $data['is_active'];
        }

        if (array_key_exists('sort_order', $data)) {
            $payload['sort_order'] = (int) $data['sort_order'];
        }

        if ($image !== null) {
            $this->deleteUploadedImage($country);
            $payload['image_path'] = $this->storeImage($image);
        }

        if ($payload !== []) {
            $country->update($payload);
        }

        return $country->fresh();
    }

    public function delete(Country $country): void
    {
        $this->deleteUploadedImage($country);
        $country->delete();
    }

    public function toggle(Country $country): Country
    {
        $country->update(['is_active' => ! $country->is_active]);

        return $country->fresh();
    }

    public function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }

    protected function assertCodeIsAvailable(string $code, ?string $ignoreId = null): void
    {
        $query = Country::query()->where('code', $code);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'formCode' => __('admin.countries.validation.code_taken'),
            ]);
        }
    }

    protected function storeImage(UploadedFile $image): string
    {
        return $image->store('countries', 'public');
    }

    protected function deleteUploadedImage(Country $country): void
    {
        if (! $country->storesUploadedImage() || $country->image_path === null) {
            return;
        }

        Storage::disk('public')->delete($country->image_path);
    }
}
