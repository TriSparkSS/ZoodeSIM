@php
    $banners = app(\App\Services\Banner\Contracts\BannerServiceInterface::class)
        ->activeForPartnerPanel()
        ->map(fn (\App\Models\Banner $banner) => [
            'id' => $banner->id,
            'title' => $banner->title,
            'image_url' => $banner->imageUrl(),
            'link_url' => $banner->link_url,
        ])
        ->all();
@endphp

@if($banners !== [])
    <div
        class="mb-5 overflow-hidden rounded-2xl border border-surface-border dark:border-brand-border"
        x-data="{ active: 0, count: {{ count($banners) }} }"
        x-init="if (count > 1) setInterval(() => active = (active + 1) % count, 6000)"
    >
        <div class="relative">
            @foreach($banners as $index => $banner)
                <div
                    x-show="active === {{ $index }}"
                    x-cloak
                    wire:key="partner-banner-{{ $banner['id'] }}"
                >
                    @if($banner['link_url'])
                        <a href="{{ $banner['link_url'] }}" target="_blank" rel="noopener noreferrer">
                            <img src="{{ $banner['image_url'] }}" alt="{{ $banner['title'] }}" class="h-40 w-full object-cover md:h-52">
                        </a>
                    @else
                        <img src="{{ $banner['image_url'] }}" alt="{{ $banner['title'] }}" class="h-40 w-full object-cover md:h-52">
                    @endif
                </div>
            @endforeach
        </div>
        @if(count($banners) > 1)
            <div class="flex justify-center gap-1.5 bg-surface-card-alt/80 py-2 dark:bg-brand-card-alt/60">
                @foreach($banners as $index => $banner)
                    <button
                        type="button"
                        class="h-2 w-2 rounded-full"
                        :class="active === {{ $index }} ? 'bg-brand-cyan' : 'bg-surface-muted/40 dark:bg-brand-muted/40'"
                        @click="active = {{ $index }}"
                        aria-label="{{ $banner['title'] }}"
                    ></button>
                @endforeach
            </div>
        @endif
    </div>
@endif
