<?php

declare(strict_types=1);

namespace FirstlightUI\CustomTabs;

use FirstlightUI\CustomTabs\Facades\CustomTabs as CustomTabsFacade;
use FirstlightUI\CustomTabs\Screens\EditorScreen;
use FirstlightUI\CustomTabs\Screens\MoreScreen;
use FirstlightUI\CustomTabs\Stores\JsonFileStore;
use FirstlightUI\CustomTabs\Stores\TabPreferenceStore;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class CustomTabsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CustomTabs::class);

        // Bind your own TabPreferenceStore to keep choices somewhere else.
        $this->app->singletonIf(TabPreferenceStore::class, fn ($app) => new JsonFileStore(
            $app->make(Filesystem::class),
            $app->storagePath('app/custom-tabs'),
        ));
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'custom-tabs');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'custom-tabs');
        $this->publishes([__DIR__.'/../lang' => $this->app->langPath('vendor/custom-tabs')], 'custom-tabs-lang');

        // Route::customTabs('main') adds the More screen at the More tab's URL and the editor
        // below it. Call it in routes/mobile.php, inside the Route::nativeGroup() of the layout
        // that shows the bar, or pass that layout.
        Route::macro('customTabs', function (string $bar = 'main', ?string $layout = null): void {
            foreach ([CustomTabsFacade::moreUrl($bar) => MoreScreen::class, CustomTabsFacade::editorUrl($bar) => EditorScreen::class] as $uri => $screen) {
                $route = Route::native($uri, $screen)->defaults('bar', $bar);
                if ($layout !== null) {
                    $route->layout($layout);
                }
            }
        });
    }
}
