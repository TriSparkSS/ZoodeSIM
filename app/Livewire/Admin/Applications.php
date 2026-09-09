<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Livewire\Concerns\WithToast;
use App\Models\Country;
use App\Models\PartnerApplication;
use App\Models\PromoCode;
use App\Services\Partner\PartnerApplicationService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use RuntimeException;

class Applications extends Component
{
    use WithAdminNavigation;
    use WithLocalizedTitle;
    use WithToast;

    public string $filter = 'all';

    /** @var array<int, array<string, mixed>> */
    public array $applications = [];

    public bool $showModal = false;

    public ?string $viewingId = null;

    public string $promoCode = '';

    protected PartnerApplicationService $applicationService;

    public function boot(PartnerApplicationService $applicationService): void
    {
        $this->applicationService = $applicationService;
    }

    public function mount(): void
    {
        $this->applications = $this->fetchApplications();
    }

    public function viewApplication(string $id): void
    {
        $application = $this->findApplication($id);

        if (! $application) {
            return;
        }

        $appModel = PartnerApplication::query()->whereKey($id)->first();

        $this->viewingId = $id;
        $this->promoCode = $appModel
            ? $this->applicationService->suggestPromoCode($appModel)
            : strtoupper(explode(' ', $application['name'])[0]).'10';
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->viewingId = null;
        $this->promoCode = '';
    }

    public function generatePromo(): void
    {
        if (! $this->viewingId) {
            return;
        }

        $appModel = PartnerApplication::query()->whereKey($this->viewingId)->first();

        if (! $appModel) {
            return;
        }

        $this->promoCode = $this->applicationService->suggestPromoCode($appModel);
    }

    public function approve(string $id): void
    {
        $application = $this->findApplication($id);

        if (! $application || $application['status'] !== 'pending') {
            return;
        }

        $promo = strtoupper(explode(' ', $application['name'])[0]).'10';
        $this->approveApplication($id, $promo);
    }

    public function reject(string $id): void
    {
        $application = $this->findApplication($id);

        if (! $application || $application['status'] !== 'pending') {
            return;
        }

        $appModel = PartnerApplication::query()->whereKey($id)->first();

        if (! $appModel) {
            return;
        }

        try {
            $this->applicationService->reject($appModel);
        } catch (RuntimeException) {
            return;
        }

        $this->updateApplication($id, 'rejected');
        $this->toast(__('admin.applications.rejected_toast', ['name' => $application['name']]), 'error');
    }

    public function approveFromModal(): void
    {
        if (! $this->viewingId) {
            return;
        }

        if (! $this->promoCode) {
            $this->toast(__('admin.applications.promo_required'), 'error');

            return;
        }

        $application = $this->findApplication($this->viewingId);

        if (! $application || $application['status'] !== 'pending') {
            return;
        }

        $this->approveApplication($this->viewingId, $this->promoCode, true);
    }

    public function rejectFromModal(): void
    {
        if (! $this->viewingId) {
            return;
        }

        $application = $this->findApplication($this->viewingId);

        if (! $application || $application['status'] !== 'pending') {
            return;
        }

        $appModel = PartnerApplication::query()->whereKey($this->viewingId)->first();

        if (! $appModel) {
            return;
        }

        try {
            $this->applicationService->reject($appModel);
        } catch (RuntimeException) {
            return;
        }

        $this->updateApplication($this->viewingId, 'rejected');
        $this->closeModal();
        $this->toast(__('admin.applications.rejected_toast', ['name' => $application['name']]), 'error');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function viewingApplication(): ?array
    {
        return $this->viewingId ? $this->findApplication($this->viewingId) : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function filteredApplications(): array
    {
        if ($this->filter === 'all') {
            return $this->applications;
        }

        return array_values(array_filter(
            $this->applications,
            fn (array $application) => $application['status'] === $this->filter
        ));
    }

    /**
     * @return array<string, int>
     */
    public function stats(): array
    {
        return [
            'pending' => count(array_filter($this->applications, fn (array $a) => $a['status'] === 'pending')),
            'approved' => count(array_filter($this->applications, fn (array $a) => $a['status'] === 'approved')),
            'rejected' => count(array_filter($this->applications, fn (array $a) => $a['status'] === 'rejected')),
            'total' => count($this->applications),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function findApplication(string $id): ?array
    {
        foreach ($this->applications as $application) {
            if ($application['id'] === $id) {
                return $application;
            }
        }

        return null;
    }

    protected function updateApplication(string $id, string $status, ?string $promo = null): void
    {
        foreach ($this->applications as $index => $application) {
            if ($application['id'] !== $id) {
                continue;
            }

            $this->applications[$index]['status'] = $status;

            if ($promo !== null) {
                $this->applications[$index]['promo'] = $promo;
            }

            break;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fetchApplications(): array
    {
        $countryNames = Country::query()->pluck('name', 'code');

        return PartnerApplication::query()
            ->latest('created_at')
            ->get()
            ->map(function (PartnerApplication $app) use ($countryNames) {
                $name = trim($app->first_name.' '.($app->last_name ?? ''));

                $promo = null;
                if ($app->status !== 'pending' && $app->partner_id) {
                    $promo = PromoCode::query()
                        ->where('partner_id', $app->partner_id)
                        ->orderByDesc('created_at')
                        ->get()
                        ->first(fn (PromoCode $code) => $code->isCurrentlyUsable())
                        ?->code
                        ?? PromoCode::query()
                            ->where('partner_id', $app->partner_id)
                            ->orderByDesc('created_at')
                            ->value('code');
                }

                return [
                    'id' => $app->id,
                    'name' => $name ?: $app->first_name,
                    'email' => $app->email,
                    'phone' => $app->phone,
                    'platforms' => $app->platforms ?? [],
                    'followers' => $app->followers,
                    'niche' => $app->niche,
                    'country' => $app->country,
                    'country_label' => $this->countryLabel($app->country, $countryNames),
                    'about' => $app->about,
                    'status' => $app->status,
                    'date' => $app->created_at?->format('Y-m-d'),
                    'gradient' => 'from-brand-cyan to-brand-purple',
                    'promo' => $promo,
                ];
            })
            ->all();
    }

    /**
     * @param  Collection<string, string>  $countryNames
     */
    protected function countryLabel(?string $code, $countryNames): string
    {
        if ($code === null || $code === '') {
            return '—';
        }

        $normalized = strtoupper($code);

        if ($countryNames->has($normalized)) {
            return (string) $countryNames->get($normalized);
        }

        $key = 'ui.countries.'.$code;
        $translated = __($key);

        return $translated === $key ? $code : $translated;
    }

    protected function approveApplication(string $applicationId, string $promoCode, bool $fromModal = false): void
    {
        $applicationModel = PartnerApplication::query()->whereKey($applicationId)->first();
        $applicationArray = $this->findApplication($applicationId);

        if (! $applicationModel || ! $applicationArray) {
            return;
        }

        try {
            $result = $this->applicationService->approve($applicationModel, $promoCode);
        } catch (ValidationException $e) {
            $this->toast(collect($e->errors())->flatten()->first() ?: __('admin.applications.promo_required'), 'error');

            return;
        } catch (RuntimeException) {
            return;
        }

        $normalized = $result['promo']->code;
        $this->updateApplication($applicationId, 'approved', $normalized);

        if ($fromModal) {
            $this->closeModal();
        }

        $this->toast(__('admin.applications.approved_toast', [
            'name' => $applicationArray['name'],
            'promo' => $normalized,
        ]));
    }

    public function render()
    {
        return $this->withLocalizedTitle(view('livewire.admin.applications', [
            'filteredApplications' => $this->filteredApplications(),
            'stats' => $this->stats(),
            'viewingApplication' => $this->viewingApplication(),
            'breadcrumbs' => $this->adminBreadcrumbs(__('admin.nav.applications')),
        ])->layout('layouts.admin', [
            'navItems' => $this->adminNavItems(),
        ]), 'admin.nav.applications');
    }
}
