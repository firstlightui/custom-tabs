<?php

declare(strict_types=1);

namespace FirstlightUI\CustomTabs\Screens;

use FirstlightUI\CustomTabs\Facades\CustomTabs;
use FirstlightUI\NativeComponent;
use Illuminate\View\View;
use Native\Mobile\Edge\Layouts\Builders\NavAction;
use Native\Mobile\Edge\Layouts\Builders\NavBarOptions;

/**
 * The screen behind the More tab: the tabs the person moved out of the bar, with Edit in the
 * navigation bar, as in the classic iOS More tab. Register it with Route::customTabs().
 */
class MoreScreen extends NativeComponent
{
    public string $bar = 'main';

    public function mount(string $bar = 'main'): void
    {
        $this->bar = $bar;
    }

    public function navTitle(): string
    {
        return CustomTabs::moreLabel($this->bar);
    }

    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()->action(
            NavAction::make('custom-tabs-edit')
                ->label(__('custom-tabs::tabs.edit'))
                ->a11yLabel(__('custom-tabs::tabs.edit'))
                ->icon(ios: 'pencil', android: 'edit')
                ->press('edit'),
        );
    }

    public function edit(): void
    {
        $this->navigate(CustomTabs::editorUrl($this->bar));
    }

    public function open(string $id): void
    {
        $tab = CustomTabs::tabs($this->bar)[$id] ?? null;
        if ($tab !== null && $tab['url'] !== '') {
            $this->navigate($tab['url']);
        }
    }

    public function render(): View
    {
        $tabs = CustomTabs::tabs($this->bar);

        // @phpstan-ignore argument.type (a package view namespace Larastan cannot resolve)
        return view('custom-tabs::more', [
            'rows' => array_map(fn (string $id) => $tabs[$id], CustomTabs::arrangement($this->bar)->more),
        ]);
    }
}
