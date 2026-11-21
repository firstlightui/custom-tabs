<?php

declare(strict_types=1);

namespace FirstlightUI\CustomTabs\Tests\Fixtures;

use FirstlightUI\CustomTabs\Facades\CustomTabs;
use Native\Mobile\Edge\Layouts\Builders\TabBar;
use Native\Mobile\Edge\Layouts\NativeLayout;
use Native\Mobile\Edge\NativeComponent;

final class TabsLayout extends NativeLayout
{
    public function usesNativeChrome(): bool
    {
        return true;
    }

    public function tabBar(NativeComponent $screen): ?TabBar
    {
        return CustomTabs::bar('main');
    }
}
