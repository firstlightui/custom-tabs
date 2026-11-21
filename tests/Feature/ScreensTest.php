<?php

use FirstlightUI\CustomTabs\Facades\CustomTabs;
use FirstlightUI\CustomTabs\Screens\EditorScreen;
use FirstlightUI\CustomTabs\Screens\MoreScreen;
use FirstlightUI\CustomTabs\Stores\TabPreferenceStore;
use Native\Mobile\Testing\Native;

/** @return array<string, mixed>|null */
function findNode(array $node, string $type): ?array
{
    if (($node['type'] ?? null) === $type) {
        return $node;
    }
    foreach ($node['children'] ?? [] as $child) {
        if (($found = findNode($child, $type)) !== null) {
            return $found;
        }
    }

    return null;
}

/** @return list<array<string, mixed>> */
function nodesOfType(array $node, string $type): array
{
    $found = ($node['type'] ?? null) === $type ? [$node] : [];
    foreach ($node['children'] ?? [] as $child) {
        $found = [...$found, ...nodesOfType($child, $type)];
    }

    return $found;
}

it('lists the tabs in More in the saved order, with badges, and opens them', function () {
    app(TabPreferenceStore::class)->put('main', ['bar' => ['reports'], 'more' => ['files', 'inbox', 'people']]);

    $more = Native::visit('/more')->assertScreen(MoreScreen::class);
    $rows = nodesOfType($more->tree(), 'firstlight.list-item');

    expect(array_map(fn (array $row) => $row['props']['headline'], $rows))->toBe(['Files', 'Inbox', 'People'])
        ->and($rows[1]['props']['trailing_value'] ?? null)->toBe('3');

    $more->call('open', 'inbox')->assertNavigatedTo('/inbox');
});

it('opens the editor with the current arrangement and the bar rules applied to every move', function () {
    $editor = Native::visit('/more/edit')->assertScreen(EditorScreen::class);
    $list = findNode($editor->tree(), 'firstlight.reorder-list');

    expect($editor->get('arrangement'))->toBe(['bar' => ['home', 'inbox', 'reports'], 'more' => ['people', 'files']])
        ->and($list['props']['row_values'])->toBe(['home', 'inbox', 'reports', 'people', 'files'])
        ->and($list['props']['row_locked'])->toBe(['1', '0', '0', '0', '0'])
        ->and($list['props']['section_labels'])->toBe(['Tab bar', 'More'])
        ->and($list['props']['helper'])->toBe('Up to 4 tabs, counting More. Home stays in the tab bar.');

    // Dropping Files into the full bar pushes the last movable tab, Reports, to the top of More.
    $editor->call('move', ['bar' => ['home', 'files', 'inbox', 'reports'], 'more' => ['people']]);

    expect($editor->get('arrangement'))->toBe(['bar' => ['home', 'files', 'inbox'], 'more' => ['reports', 'people']])
        ->and(app(TabPreferenceStore::class)->get('main'))->toBeNull();
});

it('saves on Done, so the real bar changes only then', function () {
    $editor = Native::visit('/more/edit');
    $editor->call('move', ['bar' => ['home', 'files', 'inbox'], 'more' => ['reports', 'people']]);

    expect(CustomTabs::arrangement('main')->bar)->toBe(['home', 'inbox', 'reports']);

    $editor->call('done');

    expect(CustomTabs::arrangement('main')->toArray())->toBe(['bar' => ['home', 'files', 'inbox'], 'more' => ['reports', 'people']]);
});

it('resets to the declared defaults after confirmation', function () {
    CustomTabs::save('main', ['files'], ['inbox', 'reports', 'people']);

    $editor = Native::visit('/more/edit')->call('requestReset');
    expect($editor->get('confirmingReset'))->toBeTrue();

    $editor->call('resetTabs');

    expect($editor->get('confirmingReset'))->toBeFalse()
        ->and($editor->get('arrangement'))->toBe(['bar' => ['home', 'inbox', 'reports'], 'more' => ['people', 'files']])
        ->and(app(TabPreferenceStore::class)->get('main'))->toBeNull();
});
