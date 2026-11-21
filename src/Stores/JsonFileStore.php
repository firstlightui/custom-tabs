<?php

declare(strict_types=1);

namespace FirstlightUI\CustomTabs\Stores;

use Illuminate\Filesystem\Filesystem;

/**
 * Keeps each bar's choice in a JSON file on the device, so it needs no migration and goes away
 * with the app. Values are read once per PHP process.
 */
final class JsonFileStore implements TabPreferenceStore
{
    /** @var array<string, array{bar?: mixed, more?: mixed}|null> */
    private array $read = [];

    public function __construct(
        private readonly Filesystem $files,
        private readonly string $directory,
    ) {}

    public function get(string $bar): ?array
    {
        if (! array_key_exists($bar, $this->read)) {
            $json = $this->files->exists($this->path($bar)) ? $this->files->get($this->path($bar)) : null;
            $value = $json === null ? null : json_decode($json, true);
            $this->read[$bar] = is_array($value) ? $value : null;
        }

        return $this->read[$bar];
    }

    public function put(string $bar, array $value): void
    {
        $this->files->ensureDirectoryExists($this->directory);
        $this->files->replace($this->path($bar), json_encode($value, JSON_THROW_ON_ERROR));
        $this->read[$bar] = $value;
    }

    public function forget(string $bar): void
    {
        $this->files->delete($this->path($bar));
        $this->read[$bar] = null;
    }

    private function path(string $bar): string
    {
        return $this->directory.'/'.rawurlencode($bar).'.json';
    }
}
