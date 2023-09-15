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
In your `Page.ss` file, add:

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

#### PageController
Configure your PageController to serve Inertia.
```php
public function index($request) {
    $this->inertia->render('my-component', ['prop' => 'value']);
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
