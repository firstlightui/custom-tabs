<?php

declare(strict_types=1);

namespace FirstlightUI\CustomTabs\Tests\Fixtures;

use FirstlightUI\CustomTabs\Stores\TabPreferenceStore;

final class ArrayStore implements TabPreferenceStore
{
    /** @var array<string, array<string, mixed>> */
    public array $values = [];

    public function get(string $bar): ?array
    {
        return $this->values[$bar] ?? null;
    }

    public function put(string $bar, array $value): void
    {
        $this->values[$bar] = $value;
    }

    public function forget(string $bar): void
    {
        unset($this->values[$bar]);
    }
}
