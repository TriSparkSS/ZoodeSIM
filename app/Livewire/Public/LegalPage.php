<?php

namespace App\Livewire\Public;

use App\Livewire\Concerns\WithLocalizedTitle;
use App\Models\ContentBlock;
use App\Services\Content\ContentBlockService;
use App\Support\LegalBodyParser;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Layout('layouts.public')]
class LegalPage extends Component
{
    use WithLocalizedTitle;

    public string $page = '';

    public string $heading = '';

    public string $updatedAt = '';

    /** @var list<array{type: string, text?: string, items?: list<string>}> */
    public array $sections = [];

    public function mount(ContentBlockService $blocks, LegalBodyParser $parser): void
    {
        $this->page = match (true) {
            request()->routeIs('legal.privacy') => 'privacy',
            request()->routeIs('legal.terms') => 'terms',
            request()->routeIs('legal.delete-account') => 'delete-account',
            default => throw new NotFoundHttpException,
        };

        $slug = ContentBlock::legalSlugForPage($this->page);
        $block = $slug ? $blocks->find($slug) : null;

        if (! $block) {
            throw new NotFoundHttpException;
        }

        $this->heading = (string) $block->title;
        $this->updatedAt = $block->updated_at?->timezone(config('app.timezone'))->isoFormat('LL') ?? '';
        $this->sections = $parser->parse((string) ($block->body ?? ''));
    }

    /**
     * @return list<array{page: string, href: string, label: string, active: bool}>
     */
    public function documentLinks(): array
    {
        return [
            [
                'page' => 'privacy',
                'href' => route('legal.privacy'),
                'label' => __('ui.privacy'),
                'active' => $this->page === 'privacy',
            ],
            [
                'page' => 'terms',
                'href' => route('legal.terms'),
                'label' => __('ui.terms'),
                'active' => $this->page === 'terms',
            ],
            [
                'page' => 'delete-account',
                'href' => route('legal.delete-account'),
                'label' => __('ui.delete_account'),
                'active' => $this->page === 'delete-account',
            ],
        ];
    }

    public function render()
    {
        return $this->withLocalizedTitle(
            view('livewire.public.legal-page', [
                'documentLinks' => $this->documentLinks(),
                'intro' => __('ui.legal.intro.'.$this->pageKey()),
            ]),
            'ui.'.$this->pageKey()
        );
    }

    protected function pageKey(): string
    {
        return match ($this->page) {
            'privacy' => 'privacy',
            'terms' => 'terms',
            default => 'delete_account',
        };
    }
}
