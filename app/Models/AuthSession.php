<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuthSession extends Model
{
    protected $fillable = [
        'authenticatable_type',
        'authenticatable_id',
        'guard',
        'session_id',
        'ip_address',
        'user_agent',
        'device_label',
        'login_at',
        'last_activity_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'login_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    public function scopeRevoked(Builder $query): Builder
    {
        return $query->whereNotNull('revoked_at');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    public function isCurrent(?string $sessionId = null): bool
    {
        $sessionId ??= session()->getId();

        return $this->isActive()
            && $this->session_id !== null
            && $this->session_id === $sessionId;
    }
}
