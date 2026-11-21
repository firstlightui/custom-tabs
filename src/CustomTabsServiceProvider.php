<?php

declare(strict_types=1);

namespace FirstlightUI\CustomTabs;

use FirstlightUI\CustomTabs\Stores\JsonFileStore;
use FirstlightUI\CustomTabs\Stores\TabPreferenceStore;
use Illuminate\Filesystem\Filesystem;
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
}
