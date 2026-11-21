<?php

declare(strict_types=1);

namespace FirstlightUI\CustomTabs\Screens;

use FirstlightUI\CustomTabs\Facades\CustomTabs;
use FirstlightUI\NativeComponent;
use Illuminate\View\View;
use Native\Mobile\Edge\Layouts\Builders\NavAction;
use Native\Mobile\Edge\Layouts\Builders\NavBarOptions;

/**
 * Lets the person move tabs between the bar and More with a Firstlight Reorder List. Every move
 * is checked against the bar's rules at once, without saving; Done saves and the real bar
 * changes. Leaving without Done discards the draft. Register it with Route::customTabs().
 */
class EditorScreen extends NativeComponent
{
    public string $bar = 'main';

    /** @var array{bar: list<string>, more: list<string>} */
    public array $arrangement = ['bar' => [], 'more' => []];

    public bool $confirmingReset = false;

    public function mount(string $bar = 'main'): void
    {
        $this->bar = $bar;
        $this->arrangement = CustomTabs::arrangement($bar)->toArray();
    }

    public function navTitle(): string
    {
        return __('custom-tabs::tabs.title');
    }

    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()->action(
            NavAction::make('custom-tabs-done')->label(__('custom-tabs::tabs.done'))->press('done'),
        );
    }

    /** @param array{bar?: list<string>, more?: list<string>} $value */
    public function move(array $value): void
    {
        $this->arrangement = CustomTabs::preview($this->bar, $value['bar'] ?? [], $value['more'] ?? [])->toArray();
    }

    public function done(): void
    {
        CustomTabs::save($this->bar, $this->arrangement['bar'], $this->arrangement['more']);
        $this->back();
    }

    public function requestReset(): void
    {
        $this->confirmingReset = true;
    }

    public function cancelReset(): void
    {
        $this->confirmingReset = false;
    }

    public function resetTabs(): void
    {
        $this->confirmingReset = false;
        $this->arrangement = CustomTabs::reset($this->bar)->toArray();
    }

    public function render(): View
    {
        $tabs = CustomTabs::tabs($this->bar);
        $set = CustomTabs::definition($this->bar);
        $locked = array_values(array_filter($tabs, fn (array $tab) => $tab['locked']));

        // @phpstan-ignore argument.type (a package view namespace Larastan cannot resolve)
        return view('custom-tabs::editor', [
            'items' => array_map(fn (array $tab) => [
                'value' => $tab['id'],
                'label' => $tab['label'],
                'icon' => $tab['icon'] === '' ? null : $tab['icon'],
                'locked' => $tab['locked'],
            ], array_values($tabs)),
            'sections' => [
                'bar' => __('custom-tabs::tabs.in_bar'),
                'more' => CustomTabs::moreLabel($this->bar),
            ],
            'helper' => trim(__('custom-tabs::tabs.limit', ['count' => $set->maxCount()]).' '.($locked === [] ? '' : trans_choice(
                'custom-tabs::tabs.locked',
                count($locked),
                ['tabs' => implode(', ', array_column($locked, 'label'))],
            ))),
        ]);
    }
}
