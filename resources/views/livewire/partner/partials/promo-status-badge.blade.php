<x-ui.badge :type="$promo['status']">
    @if($promo['status'] === 'locked')
        {{ __('partner.promo_codes.status_locked') }}
    @elseif($promo['status'] === 'active')
        {{ __('partner.promo_codes.status_unlocked') }}
    @elseif($promo['status'] === 'expired')
        {{ __('ui.status_expired') }}
    @elseif($promo['status'] === 'exhausted')
        {{ __('ui.status_exhausted') }}
    @else
        {{ __('ui.status_inactive') }}
    @endif
</x-ui.badge>
