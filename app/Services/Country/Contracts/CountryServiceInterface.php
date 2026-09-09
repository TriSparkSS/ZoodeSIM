<?php

namespace App\Services\Country\Contracts;

use App\Models\Country;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

interface CountryServiceInterface
{
    /**
     * @return Collection<int, Country>
     */
    public function list(): Collection;

    /**
     * @return Collection<int, Country>
     */
    public function active(): Collection;

    /**
     * @param  array{code: string, name: string, is_active?: bool, sort_order?: int}  $data
     */
    public function create(array $data, ?UploadedFile $image = null): Country;

    /**
     * @param  array{code?: string, name?: string, is_active?: bool, sort_order?: int}  $data
     */
    public function update(Country $country, array $data, ?UploadedFile $image = null): Country;

    public function delete(Country $country): void;

    public function toggle(Country $country): Country;
}
