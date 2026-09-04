<?php

namespace App\Livewire\Concerns;

trait WithToast
{
    protected function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('toast', message: $message, type: $type);
    }
}
