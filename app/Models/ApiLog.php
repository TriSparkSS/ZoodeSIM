<?php

namespace App\Models;

use Database\Factories\ApiLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiLog extends Model
{
    /** @use HasFactory<ApiLogFactory> */
    use HasFactory;

    public const TYPE_INTERNAL = 'internal';

    public const TYPE_THIRD_PARTY = 'third_party';

    public const SERVICE_PORTAL = 'portal';

    public const SERVICE_RESELLPORTAL = 'resellportal';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'type',
        'service',
        'method',
        'endpoint',
        'full_url',
        'request_headers',
        'request_body',
        'response_status',
        'response_headers',
        'response_body',
        'response_time_ms',
        'ip_address',
        'user_id',
        'reference_type',
        'reference_id',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'request_headers' => 'array',
            'request_body' => 'array',
            'response_headers' => 'array',
            'response_body' => 'array',
            'response_status' => 'integer',
            'response_time_ms' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ApiLog $log) {
            if (! $log->id) {
                $log->id = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFailed(): bool
    {
        return $this->error_message !== null
            || ($this->response_status !== null && $this->response_status >= 400);
    }

    public function isSlow(): bool
    {
        return $this->response_time_ms >= (int) config('api_logging.slow_ms', 1000);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where(function (Builder $inner) {
            $inner->whereNotNull('error_message')
                ->orWhere('response_status', '>=', 400);
        });
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeSlow(Builder $query): Builder
    {
        return $query->where('response_time_ms', '>=', (int) config('api_logging.slow_ms', 1000));
    }

    public function prettyJson(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $encoded = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return is_string($encoded) ? $encoded : '';
    }
}
