<?php

declare(strict_types=1);

namespace FirstlightUI\CustomTabs\Stores;

/**
 * Where people's tab choices are kept, by bar name. The default keeps them on the device; bind
 * your own implementation to sync them with an account, keyed by user if the app has several.
 */
interface TabPreferenceStore
{
    /** @return array{bar?: mixed, more?: mixed}|null */
    public function get(string $bar): ?array;

    /** @param array{bar: list<string>, more: list<string>} $value */
    public function put(string $bar, array $value): void;

    public function forget(string $bar): void;
}
