<?php

declare(strict_types=1);

namespace FirstlightUI\CustomTabs;

use Native\Mobile\Edge\Layouts\Builders\Tab;

/** One tab as the developer declared it, with the rules that limit where people may put it. */
final class DeclaredTab
{
    public bool $inMoreByDefault = false;

    /** @var array{0: string, 1: mixed}|null */
    public ?array $ability = null;

    public function __construct(
        public readonly string $id,
        public readonly Tab $tab,
        public readonly int $position,
        public bool $locked = false,
    ) {}
}
