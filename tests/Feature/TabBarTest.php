<?php

use FirstlightUI\CustomTabs\Facades\CustomTabs;
use FirstlightUI\CustomTabs\Stores\JsonFileStore;
use FirstlightUI\CustomTabs\Stores\TabPreferenceStore;
use FirstlightUI\CustomTabs\TabSet;
use Illuminate\Filesystem\Filesystem;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Elements\BottomNavItem;
use Native\Mobile\Edge\Layouts\Builders\Tab;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    CustomTabs::define('main', fn (TabSet $tabs) => $tabs
        ->tab('home', Tab::link('Home', '/'))->locked()
        ->tab('inbox', Tab::link('Inbox', '/inbox'))
        ->tab('reports', Tab::link('Reports', '/reports'))
        ->tab('people', Tab::link('People', '/people'))->inMoreByDefault()
        ->tab('files', Tab::link('Files', '/files'))->inMoreByDefault()
        ->more(Tab::link('More', '/more')));
});

/** @return list<string> */
function barIds(): array
{
    return array_map(fn (BottomNavItem $item) => $item->toArray(new CallbackRegistry)['props']['id'], CustomTabs::bar('main')->tabElements());
}

/** @return list<string> */
function activeTabs(string $uri): array
{
    $tree = Native::visit($uri)->tree();
    $active = [];
    $walk = function (array $node) use (&$walk, &$active) {
        if (($node['type'] ?? null) === 'bottom_nav_item' && ($node['props']['active'] ?? false)) {
            $active[] = $node['props']['label'];
        }
        foreach ($node['children'] ?? [] as $child) {
            $walk($child);
        }
    };
    $walk($tree);

    return $active;
}

it('builds the bar in the arranged order with More after the movable tabs', function () {
    app(TabPreferenceStore::class)->put('main', ['bar' => ['reports', 'inbox'], 'more' => ['files', 'people']]);

    expect(barIds())->toBe(['home', 'reports', 'inbox', 'more']);
});

it('keeps a search tab declared last after More', function () {
    CustomTabs::define('main', fn (TabSet $tabs) => $tabs
        ->tab('home', Tab::link('Home', '/'))->locked()
        ->tab('inbox', Tab::link('Inbox', '/inbox'))
        ->tab('people', Tab::link('People', '/people'))->inMoreByDefault()
        ->tab('search', Tab::search('Search'))
        ->more(Tab::link('More', '/more')));

    expect(barIds())->toBe(['home', 'inbox', 'more', 'search']);
});

it('leaves More out when nothing is in it', function () {
    app(TabPreferenceStore::class)->put('main', ['bar' => ['inbox', 'reports', 'people', 'files'], 'more' => []]);

    expect(barIds())->toBe(['home', 'inbox', 'reports', 'people', 'files']);
});

it('highlights More for screens of tabs in More, and the tab itself once it is in the bar', function () {
    expect(activeTabs('/files'))->toBe(['More'])
        ->and(activeTabs('/files/12'))->toBe(['More'])
        ->and(activeTabs('/more'))->toBe(['More'])
        ->and(activeTabs('/inbox/3'))->toBe(['Inbox'])
        ->and(activeTabs('/'))->toBe(['Home']);

    CustomTabs::save('main', ['files', 'inbox'], ['reports', 'people']);

    expect(activeTabs('/files/12'))->toBe(['Files'])
        ->and(activeTabs('/reports'))->toBe(['More']);
});

it('keeps choices in a JSON file and survives a damaged one', function () {
    $directory = sys_get_temp_dir().'/custom-tabs-'.uniqid();
    $store = new JsonFileStore(new Filesystem, $directory);
    $store->put('main', ['bar' => ['inbox'], 'more' => ['files']]);

    expect((new JsonFileStore(new Filesystem, $directory))->get('main'))->toBe(['bar' => ['inbox'], 'more' => ['files']]);

    file_put_contents($directory.'/main.json', '{not json');
    expect((new JsonFileStore(new Filesystem, $directory))->get('main'))->toBeNull();

    $store->forget('main');
    expect(file_exists($directory.'/main.json'))->toBeFalse();
    (new Filesystem)->deleteDirectory($directory);
});
