<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Models\ApiLog;
use App\Services\Logging\ApiLogQueryService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ApiLogs extends Component
{
    use WithAdminNavigation;
    use WithLocalizedTitle;
    use WithPagination;

    #[Url]
    public string $type = '';

    #[Url]
    public string $service = '';

    #[Url]
    public string $method = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $endpoint = '';

    #[Url]
    public string $user = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public bool $failed = false;

    #[Url]
    public bool $slow = false;

    protected ApiLogQueryService $logs;

    public function boot(ApiLogQueryService $logs): void
    {
        $this->logs = $logs;
    }

    public function updated(string $name): void
    {
        if ($name !== 'page') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset([
            'type',
            'service',
            'method',
            'status',
            'endpoint',
            'user',
            'dateFrom',
            'dateTo',
            'failed',
            'slow',
        ]);
        $this->resetPage();
    }

    /**
     * @return array<string, mixed>
     */
    protected function filters(): array
    {
        return [
            'type' => $this->type,
            'service' => $this->service,
            'method' => $this->method,
            'status' => $this->status,
            'endpoint' => $this->endpoint,
            'user' => $this->user,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'failed' => $this->failed,
            'slow' => $this->slow,
        ];
    }

    public function render()
    {
        return $this->withLocalizedTitle(view('livewire.admin.api-logs', [
            'logs' => $this->logs->filteredQuery($this->filters())
                ->with('user:id,name,email')
                ->orderByDesc('created_at')
                ->paginate(20),
            'breadcrumbs' => $this->adminBreadcrumbs(__('admin.nav.api_logs')),
            'types' => [ApiLog::TYPE_INTERNAL, ApiLog::TYPE_THIRD_PARTY],
            'services' => [ApiLog::SERVICE_PORTAL, ApiLog::SERVICE_RESELLPORTAL],
            'methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
        ])->layout('layouts.admin', [
            'navItems' => $this->adminNavItems(),
        ]), 'admin.nav.api_logs');
    }
}
