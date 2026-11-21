<?php

use FirstlightUI\CustomTabs\Events\TabsCustomized;
use FirstlightUI\CustomTabs\Facades\CustomTabs;
use FirstlightUI\CustomTabs\Stores\TabPreferenceStore;
use FirstlightUI\CustomTabs\TabSet;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Native\Mobile\Edge\Layouts\Builders\Tab;

function defineMain(?callable $extra = null): void
{
    CustomTabs::define('main', function (TabSet $tabs) use ($extra) {
        $tabs->tab('home', Tab::link('Home', '/'))->locked()
            ->tab('inbox', Tab::link('Inbox', '/inbox'))
            ->tab('reports', Tab::link('Reports', '/reports'))
            ->tab('people', Tab::link('People', '/people'))->inMoreByDefault()
            ->tab('files', Tab::link('Files', '/files'))->inMoreByDefault()
            ->more(Tab::link('More', '/more'));
        $extra && $extra($tabs);
    });
}

function saved(array $value): void
{
    app(TabPreferenceStore::class)->put('main', $value);
}

it('starts from the declared order and defaults', function () {
    defineMain();

    expect(CustomTabs::arrangement('main')->toArray())
        ->toBe(['bar' => ['home', 'inbox', 'reports'], 'more' => ['people', 'files']]);
});

it('keeps a saved order, ignoring unknown, duplicate and locked ids', function () {
    defineMain();
    saved(['bar' => ['files', 'gone', 'inbox', 7, 'files', 'home'], 'more' => ['reports', 'people', 'inbox']]);

    expect(CustomTabs::arrangement('main')->toArray())
        ->toBe(['bar' => ['home', 'files', 'inbox'], 'more' => ['reports', 'people']]);
});

it('ignores a saved value of the wrong shape', function () {
    defineMain();
    saved(['bar' => 'inbox', 'more' => null]);

    expect(CustomTabs::arrangement('main')->toArray())
        ->toBe(['bar' => ['home', 'inbox', 'reports'], 'more' => ['people', 'files']]);
});

it('drops More once every tab fits in the bar', function () {
    defineMain();
    saved(['bar' => ['reports', 'people', 'inbox', 'files'], 'more' => []]);

    $arrangement = CustomTabs::arrangement('main');

    expect($arrangement->toArray())->toBe(['bar' => ['home', 'reports', 'people', 'inbox', 'files'], 'more' => []])
        ->and($arrangement->showsMore())->toBeFalse();
});

it('moves the tabs that no longer fit to the top of More', function () {
    defineMain(fn (TabSet $tabs) => $tabs->maxInBar(4));
    saved(['bar' => ['reports', 'people', 'inbox', 'files'], 'more' => []]);

    // Home, two movable tabs and More make four.
    expect(CustomTabs::arrangement('main')->toArray())
        ->toBe(['bar' => ['home', 'reports', 'people'], 'more' => ['inbox', 'files']]);
});

it('fills the bar from the top of More to reach minInBar', function () {
    defineMain(fn (TabSet $tabs) => $tabs->minInBar(3));
    saved(['bar' => [], 'more' => ['files', 'people', 'inbox', 'reports']]);

    expect(CustomTabs::arrangement('main')->toArray())
        ->toBe(['bar' => ['home', 'files', 'people'], 'more' => ['inbox', 'reports']]);
});

it('puts a tab added in a later version after the tabs declared before it', function () {
    // Saved before Calendar and Notes existed.
    saved(['bar' => ['inbox', 'reports'], 'more' => ['files', 'people']]);
    CustomTabs::define('main', fn (TabSet $tabs) => $tabs
        ->tab('home', Tab::link('Home', '/'))->locked()
        ->tab('inbox', Tab::link('Inbox', '/inbox'))
        ->tab('calendar', Tab::link('Calendar', '/calendar'))
        ->tab('reports', Tab::link('Reports', '/reports'))
        ->tab('notes', Tab::link('Notes', '/notes'))->inMoreByDefault()
        ->tab('people', Tab::link('People', '/people'))->inMoreByDefault()
        ->tab('files', Tab::link('Files', '/files'))->inMoreByDefault()
        ->more(Tab::link('More', '/more')));

    // Calendar follows Inbox, not Reports; Notes, declared before both More tabs, goes first there.
    expect(CustomTabs::arrangement('main')->toArray())
        ->toBe(['bar' => ['home', 'inbox', 'calendar', 'reports'], 'more' => ['notes', 'files', 'people']]);
});

it('hides a tab the Gate denies and keeps its place for when it returns', function () {
    $canReport = false;
    Gate::define('view-reports', function ($user = null) use (&$canReport) {
        return $canReport;
    });
    CustomTabs::define('main', fn (TabSet $tabs) => $tabs
        ->tab('home', Tab::link('Home', '/'))->locked()
        ->tab('inbox', Tab::link('Inbox', '/inbox'))
        ->tab('reports', Tab::link('Reports', '/reports'))->can('view-reports')
        ->tab('people', Tab::link('People', '/people'))
        ->more(Tab::link('More', '/more')));
    saved(['bar' => ['reports', 'inbox', 'people'], 'more' => []]);

    expect(CustomTabs::arrangement('main')->toArray())->toBe(['bar' => ['home', 'inbox', 'people'], 'more' => []]);

    // Saving while Reports is hidden must not lose its first place.
    CustomTabs::save('main', ['people', 'inbox'], []);
    $canReport = true;

    expect(CustomTabs::arrangement('main')->toArray())->toBe(['bar' => ['home', 'reports', 'people', 'inbox'], 'more' => []]);
});

it('saves the checked arrangement and tells listeners', function () {
    Event::fake([TabsCustomized::class]);
    defineMain();

    $arrangement = CustomTabs::save('main', ['home', 'people', 'files', 'inbox', 'reports'], []);

    // Home and four movable tabs fill the five places, so More goes.
    expect($arrangement->toArray())->toBe(['bar' => ['home', 'people', 'files', 'inbox', 'reports'], 'more' => []])
        ->and(app(TabPreferenceStore::class)->get('main'))->toBe($arrangement->toArray());
    Event::assertDispatched(TabsCustomized::class, fn ($e) => $e->bar === 'main' && $e->arrangement === $arrangement);
});

it('resets to the declared defaults', function () {
    defineMain();
    CustomTabs::save('main', ['files'], ['inbox', 'reports', 'people']);

    expect(CustomTabs::reset('main')->toArray())->toBe(['bar' => ['home', 'inbox', 'reports'], 'more' => ['people', 'files']])
        ->and(app(TabPreferenceStore::class)->get('main'))->toBeNull();
});

it('rejects definitions the rules cannot satisfy', function (Closure $define, string $message) {
    CustomTabs::define('main', $define);

    expect(fn () => CustomTabs::arrangement('main'))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'locked tab between movable ones' => [fn (TabSet $t) => $t->tab('a', Tab::link('A', '/a'))->tab('b', Tab::link('B', '/b'))->locked()->tab('c', Tab::link('C', '/c'))->more(Tab::link('More', '/more')), 'locked tab [b] must be declared before or after'],
    'no More tab' => [fn (TabSet $t) => $t->tab('a', Tab::link('A', '/a')), 'declare the More tab'],
    'locked tab in More' => [fn (TabSet $t) => $t->tab('a', Tab::link('A', '/a'))->locked()->inMoreByDefault(), "locked tab [a] can't start in More"],
    'too many locked tabs' => [fn (TabSet $t) => $t->maxInBar(2)->minInBar(1)->tab('a', Tab::link('A', '/a'))->locked()->tab('b', Tab::link('B', '/b'))->locked()->tab('c', Tab::link('C', '/c'))->more(Tab::link('More', '/more')), "don't fit in maxInBar"],
    'more than five' => [fn (TabSet $t) => $t->maxInBar(6)->tab('a', Tab::link('A', '/a'))->more(Tab::link('More', '/more')), 'between 2 and 5'],
    'minInBar leaves no room for More' => [fn (TabSet $t) => $t->maxInBar(3)->minInBar(3)->tab('a', Tab::link('A', '/a'))->more(Tab::link('More', '/more')), 'less than maxInBar'],
]);

it('rejects bad and duplicate ids as they are declared', function (Closure $define, string $message) {
    expect(fn () => $define(new TabSet))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'uppercase' => [fn (TabSet $t) => $t->tab('Inbox', Tab::link('Inbox', '/inbox')), 'lowercase letters and digits'],
    'reserved' => [fn (TabSet $t) => $t->tab('more', Tab::link('More', '/more')), 'reserved'],
    'duplicate' => [fn (TabSet $t) => $t->tab('a', Tab::link('A', '/a'))->tab('a', Tab::link('B', '/b')), 'declared twice'],
]);
