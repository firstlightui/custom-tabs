<?php

declare(strict_types=1);

namespace FirstlightUI\CustomTabs\Tests\Fixtures;

use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

final class Screen extends NativeComponent
{
    public function render(): View
    {
        return view('screen');
    }
}
