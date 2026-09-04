<?php

namespace App\Livewire\Concerns;

trait WithLocalizedTitle
{
    /**
     * Attach a localized browser title to a Livewire view response.
     *
     * @param  \Illuminate\Contracts\View\View|\Livewire\Features\SupportPageComponents\PageComponentConfig  $view
     * @return mixed
     */
    protected function withLocalizedTitle($view, string $titleKey, array $replace = [])
    {
        return $view->title(__($titleKey, $replace));
    }
}
