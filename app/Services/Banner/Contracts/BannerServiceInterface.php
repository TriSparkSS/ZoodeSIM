<?php

namespace App\Services\Banner\Contracts;

use App\Models\Banner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

interface BannerServiceInterface
{
    /**
     * @return Collection<int, Banner>
     */
    public function list(): Collection;

    /**
     * @return Collection<int, Banner>
     */
    public function activeForUserApp(): Collection;

    /**
     * @return Collection<int, Banner>
     */
    public function activeForPartnerPanel(): Collection;

    /**
     * @param  array{title: string, link_url?: string|null, display_on: string, is_active?: bool, sort_order?: int}  $data
     */
    public function create(array $data, UploadedFile $image): Banner;

    /**
     * @param  array{title?: string, link_url?: string|null, display_on?: string, is_active?: bool, sort_order?: int}  $data
     */
    public function update(Banner $banner, array $data, ?UploadedFile $image = null): Banner;

    public function delete(Banner $banner): void;

    public function toggle(Banner $banner): Banner;
}
