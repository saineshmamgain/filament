<?php

namespace Filament\Forms;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Illuminate\View\Component as ViewComponent;

class ComponentContainer extends ViewComponent implements Htmlable, Arrayable
{
    use Concerns\BelongsToLivewire;
    use Concerns\BelongsToModel;
    use Concerns\BelongsToParentComponent;
    use Concerns\CanBeDisabled;
    use Concerns\CanBeHidden;
    use Concerns\CanBeValidated;
    use Concerns\Cloneable;
    use Concerns\EvaluatesClosures;
    use Concerns\HasColumns;
    use Concerns\HasComponents;
    use Concerns\HasFieldWrapper;
    use Concerns\HasInlineLabels;
    use Concerns\HasState;
    use Concerns\HasStateBindingModifiers;
    use Concerns\ListensToEvents;
    use Concerns\SupportsComponentFileAttachments;
    use Concerns\SupportsFileUploadFields;
    use Concerns\SupportsMultiSelectFields;
    use Concerns\SupportsSelectFields;
    use Macroable;
    use Tappable;

    protected array $meta = [];

    final public function __construct(?HasForms $livewire = null)
    {
        $this->livewire($livewire);
    }

    public static function make(?HasForms $livewire = null): static
    {
        return app(static::class, ['livewire' => $livewire]);
    }

    public function toArray(): array
    {
        return [
            'components' => array_map(function (TextInput $component) {
                return [
                    'config' => [
                        'type' => $component->getType(),
                    ],
                    'label' => $component->getLabel(),
                    'isRequired' => $component->isRequired(),
                    'statePath' => $component->getStatePath(),
                    'type' => $component::class,
                ];
            }, $this->getComponents()),
            'state' => value(function () {
                $state = [];

                foreach ($this->getFlatComponents() as $component) {
                    data_set($state, $component->getStatePath(), $component->getDefaultState());
                }

                return $state;
            }),
        ];
    }

    public function toHtml(): string
    {
        return $this->render()->render();
    }

    public function render(): View
    {
        return view('forms::component-container', array_merge($this->data(), [
            'container' => $this,
        ]));
    }
}
