<div>
    @if(! $submitted)
        <section class="border-b border-brand-border bg-gradient-to-br from-brand-cyan/8 to-brand-purple/8 px-6 py-14 text-center md:py-16">
            <h1 class="mb-3 text-3xl font-black md:text-4xl">
                <span class="gradient-text">{{ $heroTitle }}</span>
            </h1>
            <p class="mx-auto mb-8 max-w-lg text-brand-muted">{{ $heroSubtitle }}</p>

            <div class="flex flex-wrap justify-center gap-4">
                <div class="light-card min-w-[140px] px-6 py-4 text-center">
                    <div class="text-2xl font-extrabold text-brand-cyan">$1.50</div>
                    <div class="mt-1 text-xs text-brand-muted">{{ __('apply.hero.benefit_registration') }}</div>
                </div>
                <div class="light-card min-w-[140px] px-6 py-4 text-center">
                    <div class="text-2xl font-extrabold text-brand-cyan">10%</div>
                    <div class="mt-1 text-xs text-brand-muted">{{ __('apply.hero.benefit_purchase') }}</div>
                </div>
                <div class="light-card min-w-[140px] px-6 py-4 text-center">
                    <div class="text-2xl font-extrabold text-brand-cyan">200 MB</div>
                    <div class="mt-1 text-xs text-brand-muted">{{ __('apply.hero.benefit_bonus') }}</div>
                </div>
                <div class="light-card min-w-[140px] px-6 py-4 text-center">
                    <div class="text-2xl font-extrabold text-brand-cyan">$0</div>
                    <div class="mt-1 text-xs text-brand-muted">{{ __('apply.hero.benefit_free') }}</div>
                </div>
            </div>
        </section>

        <div class="mx-auto max-w-2xl px-6 py-10 pb-16">
            <x-ui.card :title="__('apply.form.title')" :padding="true">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <x-ui.form-group :label="__('apply.form.first_name')" required :error="$errors->first('firstName')">
                        <x-ui.input wire:model="firstName" placeholder="Sardor" :error="$errors->first('firstName')" />
                    </x-ui.form-group>
                    <x-ui.form-group :label="__('apply.form.last_name')">
                        <x-ui.input wire:model="lastName" placeholder="Rahimov" />
                    </x-ui.form-group>
                </div>

                <div class="mt-5 space-y-5">
                    <x-ui.form-group :label="__('apply.form.email')" required :error="$errors->first('email')">
                        <x-ui.input type="email" wire:model="email" placeholder="sardor@gmail.com" :error="$errors->first('email')" />
                    </x-ui.form-group>

                    <x-ui.form-group :label="__('apply.form.phone')" required :error="$errors->first('phone')">
                        <x-ui.input wire:model="phone" placeholder="+992 XX XXX XXXX" :error="$errors->first('phone')" />
                    </x-ui.form-group>

                    <x-ui.form-group :label="__('apply.form.platforms')" :required="true" :error="$errors->first('platforms')">
                        <p class="mb-3 text-xs text-brand-muted">{{ __('apply.form.platforms_hint') }}</p>
                        <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                            @foreach(['instagram', 'telegram', 'tiktok', 'youtube'] as $platform)
                                <button
                                    type="button"
                                    wire:click="togglePlatform('{{ $platform }}')"
                                    @class([
                                        'rounded-xl border px-2 py-3 text-center text-xs transition-all',
                                        'border-brand-cyan bg-brand-cyan/8 text-brand-cyan' => in_array($platform, $platforms),
                                        'border-brand-border text-brand-muted hover:border-brand-cyan/40' => ! in_array($platform, $platforms),
                                    ])
                                >
                                    <span class="mb-1 block text-xl">{{ ['instagram' => '📸', 'telegram' => '✈️', 'tiktok' => '🎵', 'youtube' => '▶️'][$platform] }}</span>
                                    {{ __('ui.platforms.'.$platform) }}
                                </button>
                            @endforeach
                        </div>
                    </x-ui.form-group>

                    @if(in_array('instagram', $platforms))
                        <x-ui.form-group :label="__('apply.form.instagram_url')">
                            <x-ui.input wire:model="instagram" placeholder="https://instagram.com/username" />
                        </x-ui.form-group>
                    @endif
                    @if(in_array('telegram', $platforms))
                        <x-ui.form-group :label="__('apply.form.telegram_url')">
                            <x-ui.input wire:model="telegram" placeholder="https://t.me/username" />
                        </x-ui.form-group>
                    @endif
                    @if(in_array('tiktok', $platforms))
                        <x-ui.form-group :label="__('apply.form.tiktok_url')">
                            <x-ui.input wire:model="tiktok" placeholder="https://tiktok.com/@username" />
                        </x-ui.form-group>
                    @endif
                    @if(in_array('youtube', $platforms))
                        <x-ui.form-group :label="__('apply.form.youtube_url')">
                            <x-ui.input wire:model="youtube" placeholder="https://youtube.com/@channel" />
                        </x-ui.form-group>
                    @endif

                    <x-ui.form-group :label="__('apply.form.followers')" required :error="$errors->first('followers')">
                        <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                            @foreach(['1k-10k', '10k-50k', '50k-100k', '100k-500k', '500k-1m', '1m+'] as $range)
                                <button
                                    type="button"
                                    wire:click="$set('followers', '{{ $range }}')"
                                    @class([
                                        'rounded-xl border px-3 py-3 text-xs transition-all',
                                        'border-brand-purple bg-brand-purple/8 text-brand-purple' => $followers === $range,
                                        'border-brand-border text-brand-muted hover:border-brand-purple/30' => $followers !== $range,
                                    ])
                                >
                                    {{ __('ui.followers.'.$range) }}
                                </button>
                            @endforeach
                        </div>
                    </x-ui.form-group>

                    <x-ui.form-group :label="__('apply.form.niche')" required :error="$errors->first('niche')">
                        <x-ui.select wire:model="niche" :error="$errors->first('niche')">
                            <option value="">{{ __('apply.form.niche_placeholder') }}</option>
                            @foreach(['travel', 'tech', 'lifestyle', 'business', 'education', 'entertainment', 'other'] as $nicheKey)
                                <option value="{{ $nicheKey }}">{{ __('ui.niches.'.$nicheKey) }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.form-group>

                    <x-ui.form-group :label="__('apply.form.country')">
                        <x-ui.select wire:model="country">
                            <option value="">{{ __('apply.form.country_placeholder') }}</option>
                            @foreach(['tajikistan', 'uzbekistan', 'kazakhstan', 'russia', 'uae', 'other'] as $countryKey)
                                <option value="{{ $countryKey }}">{{ __('ui.countries.'.$countryKey) }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.form-group>

                    <x-ui.form-group :label="__('apply.form.about')">
                        <textarea
                            wire:model="about"
                            rows="3"
                            placeholder="{{ __('apply.form.about_placeholder') }}"
                            class="w-full resize-y rounded-xl border border-brand-border bg-brand-card-alt px-4 py-3 text-sm outline-none focus:border-brand-cyan"
                        ></textarea>
                    </x-ui.form-group>

                    <x-ui.button class="mt-2 w-full" size="lg" wire:click="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submit">🚀 {{ __('apply.form.submit') }}</span>
                        <span wire:loading wire:target="submit" class="inline-flex items-center gap-2">
                            <span class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                            {{ __('ui.loading') }}
                        </span>
                    </x-ui.button>
                    <p class="text-center text-xs text-brand-muted">⏱ {{ __('apply.form.note') }}</p>
                </div>
            </x-ui.card>
        </div>
    @else
        <div class="mx-auto max-w-lg px-6 py-20 text-center">
            <div class="mb-5 text-6xl">🎉</div>
            <h2 class="mb-3 text-3xl font-extrabold">{{ __('apply.success.title') }}</h2>
            <p class="text-brand-muted">{{ __('apply.success.message') }}</p>
        </div>
    @endif
</div>
