@props([
    'label' => null,
    'name' => null,
    'error' => null,
    'required' => false,
])

<div {{ $attributes->only('class')->merge(['class' => 'space-y-2']) }}>
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="block text-[13px] text-surface-muted dark:text-brand-muted">
            {{ $label }}
            @if($required)
                <span class="text-brand-cyan">*</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if($error)
        <p class="text-xs text-brand-red">{{ $error }}</p>
    @endif
</div>
