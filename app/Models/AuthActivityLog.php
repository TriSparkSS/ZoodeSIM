<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuthActivityLog extends Model
{
    public const UPDATED_AT = null;

    public const EVENT_LOGIN_SUCCESS = 'login_success';

    public const EVENT_LOGIN_FAILED = 'login_failed';

    public const EVENT_LOGOUT = 'logout';

    public const EVENT_PASSWORD_CHANGED = 'password_changed';

    public const EVENT_PROFILE_UPDATED = 'profile_updated';

    public const EVENT_SESSION_TERMINATED = 'session_terminated';

    protected $fillable = [
        'authenticatable_type',
        'authenticatable_id',
        'guard',
        'event',
        'ip_address',
        'user_agent',
        'email_attempted',
        'meta',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }
}
