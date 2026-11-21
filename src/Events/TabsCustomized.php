<?php

declare(strict_types=1);

namespace FirstlightUI\CustomTabs\Events;

use FirstlightUI\CustomTabs\Arrangement;

/** A person saved or reset their tabs. Use it for analytics or to sync the choice. */
final class TabsCustomized
{
    public function __construct(
        public readonly string $bar,
        public readonly Arrangement $arrangement,
    ) {}
}
