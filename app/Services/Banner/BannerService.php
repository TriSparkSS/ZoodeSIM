<?php

namespace App\Services\Banner;

use App\Models\Banner;
use App\Services\Banner\Contracts\BannerServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BannerService implements BannerServiceInterface
{
    /**
     * @return Collection<int, Banner>
     */
    public function list(): Collection
    {
        return Banner::query()->ordered()->get();
    }

    /**
     * @return Collection<int, Banner>
     */
    public function activeForUserApp(): Collection
    {
        return Banner::query()->active()->forUserApp()->ordered()->get();
    }

    /**
     * @return Collection<int, Banner>
     */
    public function activeForPartnerPanel(): Collection
    {
        return Banner::query()->active()->forPartnerPanel()->ordered()->get();
    }

    /**
     * @param  array{title: string, link_url?: string|null, display_on: string, is_active?: bool, sort_order?: int}  $data
     */
    public function create(array $data, UploadedFile $image): Banner
    {
        $this->assertDisplayOn($data['display_on']);

        return Banner::query()->create([
            'title' => trim($data['title']),
            'image_path' => $this->storeImage($image),
            'link_url' => $this->normalizedLink($data['link_url'] ?? null),
            'display_on' => $data['display_on'],
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
    }

    /**
     * @param  array{title?: string, link_url?: string|null, display_on?: string, is_active?: bool, sort_order?: int}  $data
     */
    public function update(Banner $banner, array $data, ?UploadedFile $image = null): Banner
    {
        $payload = [];

        if (array_key_exists('title', $data)) {
            $payload['title'] = trim((string) $data['title']);
        }

        if (array_key_exists('link_url', $data)) {
            $payload['link_url'] = $this->normalizedLink($data['link_url']);
        }

        if (array_key_exists('display_on', $data)) {
            $this->assertDisplayOn((string) $data['display_on']);
            $payload['display_on'] = $data['display_on'];
        }

        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = (bool) $data['is_active'];
        }

        if (array_key_exists('sort_order', $data)) {
            $payload['sort_order'] = (int) $data['sort_order'];
        }

        if ($image !== null) {
            $this->deleteUploadedImage($banner);
            $payload['image_path'] = $this->storeImage($image);
        }

        if ($payload !== []) {
            $banner->update($payload);
        }

        return $banner->fresh() ?? $banner;
    }

    public function delete(Banner $banner): void
    {
        $this->deleteUploadedImage($banner);
        $banner->delete();
    }

    public function toggle(Banner $banner): Banner
    {
        $banner->update(['is_active' => ! $banner->is_active]);

        return $banner->fresh() ?? $banner;
    }

    protected function assertDisplayOn(string $displayOn): void
    {
        if (! in_array($displayOn, Banner::displayTargets(), true)) {
            throw ValidationException::withMessages([
                'formDisplayOn' => __('admin.banners.validation.display_on'),
            ]);
        }
    }

    protected function normalizedLink(mixed $link): ?string
    {
        if (! is_string($link)) {
            return null;
        }

        $link = trim($link);

        return $link === '' ? null : $link;
    }

    protected function storeImage(UploadedFile $image): string
    {
        return $image->store('banners', 'public');
    }

    protected function deleteUploadedImage(Banner $banner): void
    {
        if (! $banner->storesUploadedImage() || $banner->image_path === null) {
            return;
        }

        Storage::disk('public')->delete($banner->image_path);
    }
}
