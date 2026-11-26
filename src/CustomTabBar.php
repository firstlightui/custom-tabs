<?php

declare(strict_types=1);

namespace FirstlightUI\CustomTabs;

use Native\Mobile\Edge\Layouts\Builders\Tab;
use Native\Mobile\Edge\Layouts\Builders\TabBar;
use Native\Mobile\Edge\NativeRouter;

/**
 * A TabBar that also highlights More for screens that belong to a tab the person moved to More,
 * including screens opened from it.
 *
 * NativePHP picks the owning tab by tab URL and otherwise falls back to the tab marked active,
 * on iOS for the navigation stack and on Android for the selection. Without this, such screens
 * would land in the first tab's stack on iOS.
 */
final class CustomTabBar extends TabBar
{
    /** @var list<Tab> */
    private array $inMore = [];

    private ?Tab $moreTab = null;

    /** @param list<Tab> $inMore */
    public function withMore(Tab $moreTab, array $inMore): self
    {
        $this->moreTab = $moreTab;
        $this->inMore = $inMore;

        return $this;
    }

    /** NativePHP after 4.8 also passes the tab the layout asked to highlight; 4.8 ignores it. */
    public function highlight(?string $currentUrl, ?string $tabId = null): self
    {
        // Spread so the 4.8 signature that static analysis reads accepts the second argument.
        parent::highlight(...func_get_args());

        if ($this->moreTab === null || $currentUrl === null || $currentUrl === '') {
            return $this;
        }

        foreach ($this->getTabs() as $tab) {
            // A tab picked by id stays the only highlighted one.
            if ($tabId !== null && $tabId !== '' && method_exists($tab, 'getId') && $tab->getId() === $tabId) {
                return $this;
            }

            if (self::owns($tab, $currentUrl)) {
                return $this;
            }
        }
        foreach ($this->inMore as $tab) {
            if (self::owns($tab, $currentUrl)) {
                $this->moreTab->setActive(true);

                return $this;
            }
        }

        return $this;
    }

    /** The rule TabBar::highlight() and both renderers use: exact URL, or a path below it. */
    private static function owns(Tab $tab, string $currentUrl): bool
    {
        $url = $tab->getUrl();
        $path = NativeRouter::pathOf($currentUrl);

        return $url !== '' && ($url === $currentUrl || $url === $path || str_starts_with($path, $url.'/'));
    }
}
