<?php

declare(strict_types=1);

namespace FirstlightUI\CustomTabs\Tests;

use FirstlightUI\CustomTabs\CustomTabsServiceProvider;
use FirstlightUI\CustomTabs\Facades\CustomTabs;
use FirstlightUI\CustomTabs\Stores\TabPreferenceStore;
use FirstlightUI\CustomTabs\TabSet;
use FirstlightUI\CustomTabs\Tests\Fixtures\Screen;
use FirstlightUI\CustomTabs\Tests\Fixtures\TabsLayout;
use FirstlightUI\FirstlightServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\Layouts\Builders\Tab;
use Native\Mobile\NativeServiceProvider;
use Native\Mobile\UI\NativeUIServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            NativeServiceProvider::class,
            NativeUIServiceProvider::class,
            FirstlightServiceProvider::class,
            CustomTabsServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        View::addLocation(__DIR__.'/Fixtures/views');

        // NativePHP registers plugin tags from the app's plugin manifests; tests have none.
        foreach (self::firstlightComponents() as $component) {
            Blade::component('native-'.str_replace('.', '-', $component['type']), $component['blade']);
        }
    }

    /** @return list<array{type: string, element: string, blade: string}> */
    private static function firstlightComponents(): array
    {
        $manifest = json_decode((string) file_get_contents(dirname(__DIR__).'/vendor/firstlightui/nativephp/nativephp.json'), true);

        return $manifest['components'];
    }

    protected function defineEnvironment($app): void
    {
        // Before boot: NativePHP locks the element registry once the app has booted.
        foreach (self::firstlightComponents() as $component) {
            ElementRegistry::register($component['type'], $component['element']);
        }

        // Before routes resolve CustomTabs, so no test touches the real JSON store.
        $app->instance(TabPreferenceStore::class, new Fixtures\ArrayStore);
    }

    protected function defineRoutes($router): void
    {
        foreach (['/', '/inbox', '/inbox/{id}', '/reports', '/people', '/people/{id}', '/files', '/files/{id}'] as $uri) {
            Route::native($uri, Screen::class)->layout(TabsLayout::class);
        }

        CustomTabs::define('main', fn (TabSet $tabs) => $tabs
            ->tab('home', Tab::link('Home', '/', icon: 'home'))->locked()
            ->tab('inbox', Tab::link('Inbox', '/inbox', icon: 'inbox')->badge('3'))
            ->tab('reports', Tab::link('Reports', '/reports'))
            ->tab('people', Tab::link('People', '/people'))->inMoreByDefault()
            ->tab('files', Tab::link('Files', '/files', icon: 'folder'))->inMoreByDefault()
            ->maxInBar(4)
            ->more(Tab::link('More', '/more')));

        Route::customTabs('main', TabsLayout::class);
    }
}
