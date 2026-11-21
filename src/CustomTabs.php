<?php

declare(strict_types=1);

namespace FirstlightUI\CustomTabs;

use Closure;
use FirstlightUI\CustomTabs\Events\TabsCustomized;
use FirstlightUI\CustomTabs\Stores\TabPreferenceStore;
use Illuminate\Contracts\Auth\Access\Gate;
use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;

final class CustomTabs
{
    /** @var array<string, Closure(TabSet): mixed> */
    private array $definitions = [];

    /** @var array<string, TabSet> */
    private array $sets = [];

    public function __construct(
        private readonly TabPreferenceStore $store,
        private readonly Gate $gate,
    ) {}

    /** @param Closure(TabSet): mixed $definition */
    public function define(string $name, Closure $definition): void
    {
        $this->definitions[$name] = $definition;
        unset($this->sets[$name]);
    }

    /**
     * The person's tab bar, ready to return from a layout's tabBar(). Every TabBar option, such
     * as ->presentation('firstlight:tabs'), still works on it.
     */
    public function bar(string $name): CustomTabBar
    {
        $set = $this->set($name);
        $arrangement = $this->arrangement($name);
        $tabs = $set->tabs();

        $bar = new CustomTabBar;
        foreach ($arrangement->bar as $index => $id) {
            if ($index === $arrangement->moreIndex) {
                $this->addMore($bar, $set, $arrangement);
            }
            $bar->add($tabs[$id]->tab);
        }
        if ($arrangement->moreIndex === count($arrangement->bar)) {
            $this->addMore($bar, $set, $arrangement);
        }

        return $bar;
    }

    /** Where each tab the person may see sits now. */
    public function arrangement(string $name): Arrangement
    {
        return Arrangement::resolve($this->set($name), $this->store->get($name), $this->allowed(...));
    }

    /**
     * Save a new choice, such as from the editor. It is checked against the rules first, and tabs
     * the Gate hides right now keep their saved places.
     *
     * @param  list<string>  $bar
     * @param  list<string>  $more
     */
    public function save(string $name, array $bar, array $more): Arrangement
    {
        $set = $this->set($name);
        $arrangement = $this->preview($name, $bar, $more);

        $previous = $this->store->get($name) ?? [];
        $hidden = array_keys(array_filter($set->tabs(), fn (DeclaredTab $tab) => ! $this->allowed($tab)));
        $value = $arrangement->toArray();
        foreach (['bar', 'more'] as $section) {
            foreach (is_array($previous[$section] ?? null) ? array_values($previous[$section]) : [] as $index => $id) {
                if (in_array($id, $hidden, true)) {
                    array_splice($value[$section], min($index, count($value[$section])), 0, [$id]);
                }
            }
        }

        $this->store->put($name, $value);
        event(new TabsCustomized($name, $arrangement));

        return $arrangement;
    }

    /**
     * What a choice would become under the rules, without saving it. The editor shows this after
     * every move, so a tab dropped into a full bar pushes the last one into More at once.
     *
     * @param  list<string>  $bar
     * @param  list<string>  $more
     */
    public function preview(string $name, array $bar, array $more): Arrangement
    {
        return Arrangement::resolve($this->set($name), ['bar' => $bar, 'more' => $more], $this->allowed(...));
    }

    /**
     * Each tab the person may see, as drawn on this platform: id, label, icon, badge and URL.
     *
     * @return array<string, array{id: string, label: string, icon: string, badge: string, url: string, locked: bool}>
     */
    public function tabs(string $name): array
    {
        $rows = [];
        foreach ($this->set($name)->tabs() as $id => $tab) {
            if (! $this->allowed($tab)) {
                continue;
            }
            $props = $tab->tab->toElement()->toArray(new CallbackRegistry)['props'];
            $rows[$id] = [
                'id' => $id,
                'label' => (string) ($props['label'] ?? $id),
                'icon' => (string) ($props['icon'] ?? ''),
                'badge' => (string) ($props['badge'] ?? ''),
                'url' => (string) ($props['url'] ?? ''),
                'locked' => $tab->locked,
            ];
        }

        return $rows;
    }

    public function moreLabel(string $name): string
    {
        return (string) ($this->moreProps($name)['label'] ?? '');
    }

    /** The More tab's URL, where Route::customTabs() puts the More screen. */
    public function moreUrl(string $name): string
    {
        return (string) ($this->moreProps($name)['url'] ?? '');
    }

    /** Where Route::customTabs() puts the editor: below the More screen, so it opens in More. */
    public function editorUrl(string $name): string
    {
        return rtrim($this->moreUrl($name), '/').'/edit';
    }

    /** Go back to the declared defaults. */
    public function reset(string $name): Arrangement
    {
        $this->set($name);
        $this->store->forget($name);
        $arrangement = $this->arrangement($name);
        event(new TabsCustomized($name, $arrangement));

        return $arrangement;
    }

    public function definition(string $name): TabSet
    {
        return $this->set($name);
    }

    private function set(string $name): TabSet
    {
        if (! isset($this->sets[$name])) {
            if (! isset($this->definitions[$name])) {
                throw new InvalidArgumentException("No custom tab bar named [{$name}]. Declare it with CustomTabs::define().");
            }
            $set = new TabSet;
            ($this->definitions[$name])($set);
            $set->validate($name);
            $this->sets[$name] = $set;
        }

        return $this->sets[$name];
    }

    private function addMore(CustomTabBar $bar, TabSet $set, Arrangement $arrangement): void
    {
        $more = $set->moreTab();
        if ($arrangement->showsMore() && $more !== null) {
            $tabs = $set->tabs();
            $bar->add($more);
            $bar->withMore($more, array_map(fn (string $id) => $tabs[$id]->tab, $arrangement->more));
        }
    }

    /** @return array<string, mixed> */
    private function moreProps(string $name): array
    {
        $more = $this->set($name)->moreTab();
        if ($more === null) {
            throw new InvalidArgumentException("Custom tab bar [{$name}] has no More tab; declare it with more().");
        }

        return $more->toElement()->toArray(new CallbackRegistry)['props'];
    }

    private function allowed(DeclaredTab $tab): bool
    {
        return $tab->ability === null || $this->gate->allows(...$tab->ability);
    }
}
