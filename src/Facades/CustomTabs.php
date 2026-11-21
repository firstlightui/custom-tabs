<?php

declare(strict_types=1);

namespace FirstlightUI\CustomTabs\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void define(string $name, \Closure $definition)
 * @method static \FirstlightUI\CustomTabs\CustomTabBar bar(string $name)
 * @method static \FirstlightUI\CustomTabs\Arrangement arrangement(string $name)
 * @method static \FirstlightUI\CustomTabs\Arrangement save(string $name, list<string> $bar, list<string> $more)
 * @method static \FirstlightUI\CustomTabs\Arrangement reset(string $name)
 * @method static \FirstlightUI\CustomTabs\TabSet definition(string $name)
 * @method static \FirstlightUI\CustomTabs\Arrangement preview(string $name, list<string> $bar, list<string> $more)
 * @method static array<string, array{id: string, label: string, icon: string, badge: string, url: string, locked: bool}> tabs(string $name)
 * @method static string moreLabel(string $name)
 * @method static string moreUrl(string $name)
 * @method static string editorUrl(string $name)
 *
 * @see \FirstlightUI\CustomTabs\CustomTabs
 */
final class CustomTabs extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \FirstlightUI\CustomTabs\CustomTabs::class;
    }
}
