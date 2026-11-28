# Firstlight Custom Tabs

Let people choose and reorder the tabs of your NativePHP Mobile app, within the tabs, locks and limits you declare.

It needs Firstlight 1.2 or later and NativePHP Mobile 4.9 or later.

```bash
composer require firstlightui/custom-tabs
```

See [Let people customise their tabs](https://firstlightui.dev/docs/how-to/let-people-customise-tabs) for the full recipe.

```php
use FirstlightUI\CustomTabs\Facades\CustomTabs;
use FirstlightUI\CustomTabs\TabSet;
use Native\Mobile\Edge\Layouts\Builders\Tab;

// AppServiceProvider::boot()
CustomTabs::define('main', fn (TabSet $tabs) => $tabs
    ->tab('home', Tab::link('Home', '/'))->locked()
    ->tab('inbox', Tab::link('Inbox', '/inbox'))
    ->tab('reports', Tab::link('Reports', '/reports'))->can('view-reports')
    ->tab('files', Tab::link('Files', '/files'))->inMoreByDefault()
    ->more(Tab::link('More', '/more')));

// In your layout
public function tabBar(NativeComponent $screen): ?TabBar
{
    return CustomTabs::bar('main');
}

// routes/mobile.php: the More screen at the More tab's URL, and the editor below it (/more/edit)
Route::customTabs('main', TabsLayout::class);
```

The More screen lists the tabs people moved out of the bar, with Edit in the navigation bar. The editor shows the bar and More as two sections of a Firstlight Reorder List: people drag tabs, or use the VoiceOver and TalkBack actions, and each move is checked against your rules at once. A tab dropped into a full bar pushes the last movable tab into More. Nothing is saved until Done; Reset to default asks first.

To put the editor in your own settings screen instead, extend `FirstlightUI\CustomTabs\Screens\EditorScreen`, or use `CustomTabs::preview()` and `CustomTabs::save()` with your own `<firstlight:reorder-list>`. The strings are in `lang/en/tabs.php`; publish them with `php artisan vendor:publish --tag=custom-tabs-lang`.

Free and MIT licensed.
