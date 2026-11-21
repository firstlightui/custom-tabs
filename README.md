# Firstlight Custom Tabs

Let people choose and reorder the tabs of your NativePHP Mobile app, within the tabs, locks and limits you declare.

Work in progress: the PHP core is in place; the editor screen, the More screen and the reorder control are being built. Not released yet.

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
```

Free and MIT licensed.
