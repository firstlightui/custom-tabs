<?php

declare(strict_types=1);

namespace FirstlightUI\CustomTabs\Tests;

use FirstlightUI\CustomTabs\CustomTabsServiceProvider;
use FirstlightUI\CustomTabs\Stores\TabPreferenceStore;
use FirstlightUI\CustomTabs\Tests\Fixtures\Screen;
use FirstlightUI\CustomTabs\Tests\Fixtures\TabsLayout;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Native\Mobile\NativeServiceProvider;
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
            CustomTabsServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        View::addLocation(__DIR__.'/Fixtures/views');
        $this->app->instance(TabPreferenceStore::class, new Fixtures\ArrayStore);
    }

    protected function defineRoutes($router): void
    {
        foreach (['/', '/inbox', '/inbox/{id}', '/reports', '/people', '/people/{id}', '/files', '/files/{id}', '/more'] as $uri) {
            Route::native($uri, Screen::class)->layout(TabsLayout::class);
        }
    }
}
