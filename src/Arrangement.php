<?php

declare(strict_types=1);

namespace FirstlightUI\CustomTabs;

/**
 * Where each allowed tab sits: in the bar, in order, or in More. Always built through
 * resolve(), so a stale or hand-edited saved choice can never break the bar.
 */
final class Arrangement
{
    /**
     * @param  list<string>  $bar  tab ids in the bar, in order, without More
     * @param  list<string>  $more  tab ids listed on the More screen, in order
     * @param  int  $moreIndex  where More goes in the bar, when shown
     */
    private function __construct(
        public readonly array $bar,
        public readonly array $more,
        public readonly int $moreIndex,
    ) {}

    public function showsMore(): bool
    {
        return $this->more !== [];
    }

    /** @return array{bar: list<string>, more: list<string>} */
    public function toArray(): array
    {
        return ['bar' => $this->bar, 'more' => $this->more];
    }

    /**
     * Apply a saved choice to the current declaration:
     *
     * 1. Drop ids that are no longer declared or that the Gate now hides.
     * 2. Put locked tabs back at the start or end, where they were declared.
     * 3. Put tabs added since the choice was saved into their default section, after the last
     *    tab there that was declared before them, or first if there is none.
     * 4. Enforce maxInBar (counting More): tabs that don't fit go to the top of More.
     * 5. Enforce minInBar: fill the bar from the top of More.
     *
     * In the bar, More sits after the movable tabs and before locked tabs declared last, so a
     * search tab declared last stays last, as iOS expects.
     *
     * @param  array{bar?: mixed, more?: mixed}|null  $saved
     * @param  callable(DeclaredTab): bool  $allowed
     */
    public static function resolve(TabSet $set, ?array $saved, callable $allowed): self
    {
        $declared = array_filter($set->tabs(), $allowed);
        $unlocked = array_filter($declared, fn (DeclaredTab $tab) => ! $tab->locked);
        $firstUnlocked = min(array_map(fn (DeclaredTab $tab) => $tab->position, $unlocked) ?: [PHP_INT_MAX]);

        $lockedStart = $lockedEnd = [];
        foreach ($declared as $id => $tab) {
            if ($tab->locked) {
                $tab->position < $firstUnlocked ? $lockedStart[] = $id : $lockedEnd[] = $id;
            }
        }

        $bar = self::ids($saved['bar'] ?? null, $unlocked);
        $more = array_values(array_diff(self::ids($saved['more'] ?? null, $unlocked), $bar));

        foreach ($unlocked as $id => $tab) {
            if (in_array($id, $bar, true) || in_array($id, $more, true)) {
                continue;
            }
            if ($tab->inMoreByDefault) {
                $more = self::insertByDeclaration($more, $tab, $declared);
            } else {
                $bar = self::insertByDeclaration($bar, $tab, $declared);
            }
        }

        $locked = count($lockedStart) + count($lockedEnd);
        if ($more !== [] || $locked + count($bar) > $set->maxCount()) {
            $room = max(0, $set->maxCount() - $locked - 1);
            $more = [...array_slice($bar, $room), ...$more];
            $bar = array_slice($bar, 0, $room);
        }

        while ($more !== [] && $locked + count($bar) < $set->minCount()) {
            $bar[] = array_shift($more);
        }

        return new self([...$lockedStart, ...$bar, ...$lockedEnd], $more, count($lockedStart) + count($bar));
    }

    /**
     * Saved ids that are still declared, allowed and movable, once each, in saved order.
     *
     * @param  array<string, DeclaredTab>  $unlocked
     * @return list<string>
     */
    private static function ids(mixed $saved, array $unlocked): array
    {
        if (! is_array($saved)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            $saved,
            fn (mixed $id) => is_string($id) && isset($unlocked[$id]),
        )));
    }

    /**
     * @param  list<string>  $section
     * @param  array<string, DeclaredTab>  $declared
     * @return list<string>
     */
    private static function insertByDeclaration(array $section, DeclaredTab $tab, array $declared): array
    {
        $index = 0;
        foreach ($section as $i => $id) {
            if ($declared[$id]->position < $tab->position) {
                $index = $i + 1;
            }
        }
        array_splice($section, $index, 0, [$tab->id]);

        return $section;
    }
}
