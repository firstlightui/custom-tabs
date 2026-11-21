<?php

declare(strict_types=1);

namespace FirstlightUI\CustomTabs;

use InvalidArgumentException;
use LogicException;
use Native\Mobile\Edge\Layouts\Builders\Tab;

/**
 * The tabs one bar may show, declared once in a service provider:
 *
 *   CustomTabs::define('main', fn (TabSet $tabs) => $tabs
 *       ->tab('home', Tab::link('Home', '/'))->locked()
 *       ->tab('inbox', Tab::link('Inbox', '/inbox'))
 *       ->tab('files', Tab::link('Files', '/files'))->inMoreByDefault()
 *       ->more(Tab::link('More', '/more')));
 *
 * Declaration order is the default order. Modifiers apply to the tab added last.
 */
final class TabSet
{
    public const MORE_ID = 'more';

    public const MAX_IN_BAR_LIMIT = 5;

    /** @var array<string, DeclaredTab> */
    private array $tabs = [];

    private ?string $last = null;

    private ?Tab $more = null;

    private int $maxInBar = self::MAX_IN_BAR_LIMIT;

    private int $minInBar = 2;

    /**
     * Add a tab. The id is what saved choices refer to, so keep it stable; it replaces the id
     * Tab::link() derives from the label, which changes when the label is translated.
     */
    public function tab(string $id, Tab $tab): self
    {
        if (preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $id) !== 1) {
            throw new InvalidArgumentException("Tab id [{$id}] must be lowercase letters and digits joined by hyphens.");
        }
        if ($id === self::MORE_ID) {
            throw new InvalidArgumentException('Tab id [more] is reserved for the More tab; declare it with more().');
        }
        if (isset($this->tabs[$id])) {
            throw new InvalidArgumentException("Tab id [{$id}] is declared twice.");
        }

        $this->tabs[$id] = new DeclaredTab($id, $tab->id($id), count($this->tabs), locked: $tab->isSearchTab());
        $this->last = $id;

        return $this;
    }

    /** Keep the last tab in the bar at its declared place. Locked tabs come first or last. */
    public function locked(): self
    {
        $this->lastTab()->locked = true;

        return $this;
    }

    /** Start the last tab in More instead of the bar. */
    public function inMoreByDefault(): self
    {
        $this->lastTab()->inMoreByDefault = true;

        return $this;
    }

    /** Show the last tab only to people the Gate allows; it keeps its saved place while hidden. */
    public function can(string $ability, mixed $arguments = []): self
    {
        $this->lastTab()->ability = [$ability, $arguments];

        return $this;
    }

    /** The tab that opens the More screen. It appears only while More holds at least one tab. */
    public function more(Tab $tab): self
    {
        $this->more = $tab->id(self::MORE_ID);

        return $this;
    }

    /** Most tabs in the bar, counting More. At most 5, as Material 3 and the iPhone tab bar allow. */
    public function maxInBar(int $count): self
    {
        $this->maxInBar = $count;

        return $this;
    }

    /** Fewest tabs in the bar, not counting More. */
    public function minInBar(int $count): self
    {
        $this->minInBar = $count;

        return $this;
    }

    /** @return array<string, DeclaredTab> */
    public function tabs(): array
    {
        return $this->tabs;
    }

    public function moreTab(): ?Tab
    {
        return $this->more;
    }

    public function maxCount(): int
    {
        return $this->maxInBar;
    }

    public function minCount(): int
    {
        return $this->minInBar;
    }

    /** Fail at the first render instead of drawing a bar the rules can't fill. */
    public function validate(string $name): void
    {
        $fail = fn (string $message) => throw new InvalidArgumentException("Custom tab bar [{$name}]: {$message}");

        if ($this->tabs === []) {
            $fail('declare at least one tab.');
        }
        if ($this->maxInBar < 2 || $this->maxInBar > self::MAX_IN_BAR_LIMIT) {
            $fail('maxInBar must be between 2 and '.self::MAX_IN_BAR_LIMIT.'.');
        }
        if ($this->minInBar < 1 || $this->minInBar > $this->maxInBar - 1) {
            $fail('minInBar must be at least 1 and less than maxInBar, which leaves room for More.');
        }

        $locked = array_filter($this->tabs, fn (DeclaredTab $tab) => $tab->locked);
        $unlocked = array_filter($this->tabs, fn (DeclaredTab $tab) => ! $tab->locked);

        foreach ($locked as $tab) {
            if ($tab->inMoreByDefault) {
                $fail("locked tab [{$tab->id}] can't start in More.");
            }
            if ($unlocked !== [] && $tab->position > min(array_map(fn ($t) => $t->position, $unlocked))
                && $tab->position < max(array_map(fn ($t) => $t->position, $unlocked))) {
                $fail("locked tab [{$tab->id}] must be declared before or after every unlocked tab.");
            }
        }
        if ($unlocked !== [] && $this->more === null) {
            $fail('people can move tabs to More, so declare the More tab with more().');
        }
        if (count($locked) + ($unlocked === [] ? 0 : 1) > $this->maxInBar) {
            $fail('the locked tabs and More don\'t fit in maxInBar.');
        }
    }

    private function lastTab(): DeclaredTab
    {
        if ($this->last === null) {
            throw new LogicException('Call tab() before locked(), inMoreByDefault() or can().');
        }

        return $this->tabs[$this->last];
    }
}
