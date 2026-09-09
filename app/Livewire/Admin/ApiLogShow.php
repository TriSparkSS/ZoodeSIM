<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Models\ApiLog;
use App\Services\Logging\SensitiveDataRedactor;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiLogShow extends Component
{
    use WithAdminNavigation;
    use WithLocalizedTitle;

    public string $logId;

    public function mount(string $log): void
    {
        $this->logId = $log;
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(SensitiveDataRedactor $redactor): array
    {
        $log = ApiLog::query()->with('user:id,name,email')->whereKey($this->logId)->first();

        if (! $log) {
            throw new NotFoundHttpException;
        }

        return [
            'id' => $log->id,
            'type' => $log->type,
            'service' => $log->service,
            'method' => $log->method,
            'endpoint' => $log->endpoint,
            'full_url' => $redactor->redactString((string) $log->full_url),
            'created_at' => $log->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            'ip_address' => $log->ip_address,
            'user' => $log->user?->email ?? $log->user_id,
            'reference' => $log->reference_type && $log->reference_id
                ? $log->reference_type.': '.$log->reference_id
                : null,
            'response_status' => $log->response_status,
            'response_time_ms' => $log->response_time_ms,
            'error_message' => $log->error_message,
            'failed' => $log->isFailed(),
            'request_headers' => $log->prettyJson($redactor->redactHeaders($log->request_headers ?? [])),
            'request_body' => $log->prettyJson($redactor->redact($log->request_body)),
            'response_headers' => $log->prettyJson($redactor->redactHeaders($log->response_headers ?? [])),
            'response_body' => $log->prettyJson($redactor->redact($log->response_body)),
        ];
    }

    public function render()
    {
        return $this->withLocalizedTitle(view('livewire.admin.api-log-show', [
            'log' => $this->detail(app(SensitiveDataRedactor::class)),
            'breadcrumbs' => [
                ['label' => __('ui.admin_panel'), 'href' => route('admin.dashboard')],
                ['label' => __('admin.nav.api_logs'), 'href' => route('admin.api-logs')],
                ['label' => __('admin.api_logs.details_title'), 'active' => true],
            ],
        ])->layout('layouts.admin', [
            'navItems' => $this->adminNavItems(),
        ]), 'admin.api_logs.details_title');
    }
}
