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

    public const EVENT_PRICING_SLAB_CREATED = 'pricing_slab_created';

    public const EVENT_PRICING_SLAB_UPDATED = 'pricing_slab_updated';

    public const EVENT_PRICING_SLAB_ACTIVATED = 'pricing_slab_activated';

    public const EVENT_PRICING_SLAB_DEACTIVATED = 'pricing_slab_deactivated';

    public const EVENT_PRICING_SLAB_DELETED = 'pricing_slab_deleted';

    public const EVENT_WITHDRAWAL_REQUESTED = 'withdrawal_requested';

    public const EVENT_WITHDRAWAL_COMPLETED = 'withdrawal_completed';

    public const EVENT_WITHDRAWAL_REJECTED = 'withdrawal_rejected';

    public const EVENT_PARTNER_BALANCE_CREDITED = 'partner_balance_credited';

    public const EVENT_PARTNER_BALANCE_DEBITED = 'partner_balance_debited';

    public const EVENT_USER_UPDATED = 'user_updated';

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
