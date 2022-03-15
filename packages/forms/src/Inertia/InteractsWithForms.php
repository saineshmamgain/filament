<?php

namespace Filament\Forms\Inertia;

use Filament\Forms\ComponentContainer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Livewire\TemporaryUploadedFile;
use Livewire\WithFileUploads;

trait InteractsWithForms
{
    use WithFileUploads;

    public array $componentFileAttachments = [];

    protected ?array $cachedForms = null;

    protected bool $isCachingForms = false;

    public function __get($property)
    {
        if (! $this->isCachingForms && $form = $this->getCachedForm($property)) {
            return $form;
        }

        return parent::__get($property);
    }

    public function dispatchFormEvent(...$args): void
    {
        foreach ($this->getCachedForms() as $form) {
            $form->dispatchEvent(...$args);
        }
    }

    protected function cacheForm(string $name): ComponentContainer
    {
        $this->isCachingForms = true;

        if ($this->cachedForms === null) {
            $this->cacheForms();
        } else {
            $this->cachedForms[$name] = $this->getUncachedForms()[$name];
        }

        $this->isCachingForms = false;

        return $this->cachedForms[$name];
    }

    protected function cacheForms(): array
    {
        $this->isCachingForms = true;

        $this->cachedForms = $this->getUncachedForms();

        $this->isCachingForms = false;

        return $this->cachedForms;
    }

    protected function getUncachedForms(): array
    {
        return array_merge($this->getTraitForms(), $this->getForms());
    }

    protected function getTraitForms(): array
    {
        $forms = [];

        foreach (class_uses_recursive($class = static::class) as $trait) {
            if (method_exists($class, $method = 'get' . class_basename($trait) . 'Forms')) {
                $forms = array_merge($forms, $this->{$method}());
            }
        }

        return $forms;
    }

    protected function focusConcealedComponents(array $statePaths): void
    {
        $componentToFocus = null;

        foreach ($this->getCachedForms() as $form) {
            if ($componentToFocus = $form->getInvalidComponentToFocus($statePaths)) {
                break;
            }
        }

        if ($concealingComponent = $componentToFocus?->getConcealingComponent()) {
            $this->dispatchBrowserEvent('expand-concealing-component', [
                'id' => $concealingComponent->getId(),
            ]);
        }
    }

    protected function getCachedForm($name): ?ComponentContainer
    {
        return $this->getCachedForms()[$name] ?? null;
    }

    protected function getCachedForms(): array
    {
        if ($this->cachedForms === null) {
            return $this->cacheForms();
        }

        return $this->cachedForms;
    }

    protected function getFormModel(): Model | string | null
    {
        return null;
    }

    protected function getFormSchema(): array
    {
        return [];
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->makeForm()
                ->schema($this->getFormSchema())
                ->model($this->getFormModel())
                ->statePath($this->getFormStatePath()),
        ];
    }

    protected function getFormStatePath(): ?string
    {
        return null;
    }

    protected function makeForm(): ComponentContainer
    {
        return ComponentContainer::make();
    }
}
