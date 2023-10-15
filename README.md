# Inertia.js Silverstripe adapter
Inertia.js adapter for Silverstripe, based on [inertia-bundle](https://github.com/rompetomp/inertia-bundle/tree/master).

Visit [inertiajs.com](https://inertiajs.com/) to learn more.

## Getting started
Install the server adapter.

```sh
composer require cambis/silverstripe-inertia
```

Install the preferred client adapter.
### React
```sh
yarn add -D @inertiajs/react
```

### Vue
```sh
yarn add -D @inertiajs/vue3
```

### Svelte
```sh
yarn add -D @inertiajs/svelte
```

## Configuration
Here is a basic configuration to get started with.

### Server-side
First, configure your Silverstripe application with the following:
#### Config file
Create a config file:

```yaml
---
Name: inertia
After:
  - requestprocessors
---
SilverStripe\Core\Injector\Injector:
  SilverStripe\Control\Director:
    properties:
      Middlewares:
        InertiaMiddleware: '%$Cambis\Inertia\Control\Middleware\InertiaMiddleware'

PageController:
  extensions:
    - Cambis\Inertia\Extension\InertiaPageControllerExtension
```

#### Template
In your root `Page.ss` template, add:
```diff
<head>
...
+$InertiaHead($PageData)
</head>
<body>
-$Layout
+$InertiaBody($PageData)
...
</body>
```

The root template location is optionally configurable:
```yml
Cambis\Inertia\Inertia:
  root_view: MyAlternativePage
```

#### PageController
Configure your `PageController` class to serve Inertia.
```php
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;

class PageController extends ContentController
{
  /**
   * @return HTTPResponse
   */
  public function index(HTTPRequest $request) {
      return $this->inertia->render('Dashboard', ['prop' => 'value']);
  }
}
```

If your IDE supports the `@mixin` directive, add it to your `PageController` for autocomplete:
```diff
+use Cambis\Inertia\Extension\InertiaPageControllerExtension;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;

+/**
+ * @mixin InertiaPageControllerExtension
+ */
class PageController extends ContentController
{
  /**
   * @return HTTPResponse
   */
  public function index(HTTPRequest $request) {
      return $this->inertia->render('Dashboard', ['prop' => 'value']);
  }
}
```

Alternatively, you can use the `@property` directive:
```diff
+use Cambis\Inertia\Inertia;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;

+/**
+ * @property Inertia $inertia
+ */
class PageController extends ContentController
{
  /**
   * @return HTTPResponse
   */
  public function index(HTTPRequest $request) {
      return $this->inertia->render('Dashboard', ['prop' => 'value']);
  }
}
```

### Client-side
Once your Silverstripe application is setup, you can configure the client application.
#### React
```jsx
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

createInertiaApp({
  resolve: (name) => import(`./Pages/${name}`),
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />);
  },
});
```

#### Vue
```jsx
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'

createInertiaApp({
  resolve: (name) => import(`./Pages/${name}`),
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el)
  },
})
```

#### Svelte
```jsx
import { createInertiaApp } from '@inertiajs/svelte'

createInertiaApp({
  resolve: (name) => import(`./Pages/${name}`),
  setup({ el, App, props }) {
    new App({ target: el, props })
  },
})
```
## Lazy data evaluation
Sometimes you don't want to re-evaluate data when making visits to the same page type. This can be accomplised
server-side by using a callback function or `lazy()`.

Check the [offical documentation](https://inertiajs.com/partial-reloads) for client side configuration.

```php
return $this->inertia->render('Dashboard', [
    // ALWAYS included on first visit...
    // OPTIONALLY included on partial reloads...
    // ALWAYS evaluated...
    'foo' => 'bar',

    // ALWAYS included on first visit...
    // OPTIONALLY included on partial reloads...
    // ONLY evaluated when needed...
    'foo' => static function (): string { return 'bar'; },

    // NEVER included on first visit...
    // OPTIONALLY included on partial reloads...
    // ONLY evaluated when needed...
    'foo' => $this->inertia->lazy(static function (): string { return 'bar'; }),
]);
```

## Sharing data
You can share props between all components using the `$this->inertia->share(string, mixed)` function.
One use case is populating the navigation menu for a website, this can be accomplished using a `SilverStripe\Core\Extension`.
```php
<?php

namespace App\Inertia\Extension;

use Page;
use PageController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Extension;

/**
 * @method PageController&$this getOwner()
 */
class InertiaControllerMenuExtension extends Extension
{
    public function beforeCallActionHandler(HTTPRequest $request, string $action): void
    {
        $inertia = $this->getOwner()->inertia;
        $items = [];

        /** @var Page $page */
        foreach ($this->getOwner()->getMenu(1) as $page) {
            $item = [
                'id' => static function () use ($page): int {
                    return $page->ID;
                },
                'menuTitle' => static function () use ($page): string {
                    return $page->MenuTitle;
                },
                'link' => static function () use ($page): string {
                    return $page->Link();
                },
            ];

            $items[] = $item;
        }

        $inertia->share('menu', $items);
    }
}
```

## View data
You can pass data to your root Silverstripe template via the `render()` parameter in the render function:
```php
return $this->inertia->render('Dashboard', ['prop' => 'value'], ['Title' => 'My title']);
```

You can also pass data using the `$this->inertia->viewData(string, mixed)` function:
```php
$this->inertia->viewData(['Title' => 'My title'])
```

Data is accessible using the `$ViewData` variable
```php
$ViewData.Title // 'My title'
```

## Asset versioning
By default, the middleware checks for the config variables `asset_url` and `manifest_file` to get the current asset version.
```yml
Cambis\Inertia\Inertia:
  assert_url: https://example.cdn.com/manifest.json

# OR
Cambis\Inertia\Inertia:
  manifest_file: /themes/default/dist/manifest.json
```

You can specify your application's asset version by extending the `Cambis\Inertia\Control\Middleware\InertiaMiddleware` class.
```php
namespace App\Inertia\Control\Middleware;

use Cambis\Inertia\Control\Middleware\InertiaMiddleware as BaseMiddleware;
use SilverStripe\Control\HTTPRequest;

class InertiaMiddleware extends BaseMiddleware
{
    public function version(HTTPRequest $request): ?string
    {
        // Custom logic here
    }
}
```

Don't forget to update your configuration if you do this!
```diff
SilverStripe\Core\Injector\Injector:
  SilverStripe\Control\Director:
    properties:
      Middlewares:
-      InertiaMiddleware: '%$Cambis\Inertia\Control\Middleware\InertiaMiddleware'
+      InertiaMiddleware: '%$App\Inertia\Control\Middleware\InertiaMiddleware'
```

Alternatively, you can use the `$this->inertia->version(mixed)` function to set the current asset version
```php
$this->inertia->version('foo');
$this->inertia->version(static function (): string { return 'foo'; }) // Lazily...
```

### Cache busting
Check the [offical documentation](https://inertiajs.com/asset-versioning) for information on cache busting.

## Server-side rendering
To enable server-side rendering, first enable the following config variables `ssr_enabled` and `ssr_host`:

```yml
Cambis\Inertia\Inertia:
  ssr_enabled: true
  ssr_host: https://my-ssr-host/render
```

Check the [official documentation](https://inertiajs.com/server-side-rendering) for information on client-side setup.
