<?php

namespace Cambis\Inertia;

use Closure;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\View\ArrayData;

class Inertia
{
    use Configurable;
    use Injectable;

    /**
     * The root template of your application, defaults to 'Page'.
     *
     * @config
     */
    private static string $root_view = 'Page';

    /**
     * The location of your manifest file, used for versioning.
     *
     * @config
     */
    private static ?string $manifest_file = null;

    /**
     * @config
     */
    private static bool $ssr_enabled = false;

    /**
     * @config
     */
    private static string $ssr_host = 'http://127.0.0.1:13714';

    protected array $sharedProps = [];

    protected array $sharedViewData = [];

    /** @var Closure|string|null */
    protected mixed $version = null;

    public function share(string $key, mixed $value = null): void
    {
        $this->sharedProps[$key] = $value;
    }

    public function getShared(?string $key = null): mixed
    {
        if ($key) {
            return $this->sharedProps[$key] ?? null;
        }

        return $this->sharedProps;
    }

    public function viewData(string $key, mixed $value = null): void
    {
        $this->sharedViewData[$key] = $value;
    }

    public function getViewData(?string $key = null): mixed
    {
        if ($key) {
            return $this->sharedViewData[$key] ?? null;
        }

        return $this->sharedViewData;
    }

    /**
     * @param Closure|string|null $version
     */
    public function version(mixed $version): void
    {
        $this->version = $version;
    }

    public function getRootView(): ?string
    {
        return $this->config()->get('root_view');
    }

    public function getManifestFile(): ?string
    {
        return $this->config()->get('manifest_file');
    }

    public function getVersion(): string
    {
        $version = $this->version instanceof Closure
            ? call_user_func($this->version)
            : $this->version;

        return (string) $version;
    }

    /**
     * Check if it using ssr.
     */
    public function isSsr(): bool
    {
        return (bool) $this->config()->get('ssr_enabled');
    }

    public function getSsrHost(): string
    {
        return $this->config()->get('ssr_host');
    }

    /**
     * @param callable|string|array $name
     */
    public function lazy(mixed $callback): LazyProp
    {
        return LazyProp::create($callback);
    }

    /**
     * @param string|HTTPResponse $url
     */
    public function location(mixed $url): HTTPResponse
    {
        if ($url instanceof HTTPResponse && $url->isRedirect()) {
            $url = $url->getHeader('location');
        }

        if ($this->getRequest()->getHeader('X-Inertia')) {
            return HTTPResponse::create()
                ->setStatusCode(409)
                ->addHeader('X-Inertia-Location', $url);
        }

        return HTTPResponse::create()
            ->addHeader('location', $url)
            ->setStatusCode(302);
    }

    /**
     * ```
     * <?php
     * public function index($request)
     * {
     *     $this->inertia->render('my-component', ['prop' => 'value']);
     * }
     * ```
     *
     * @see https://inertiajs.com/responses
     * @param string $component component name
     * @param array $props component properties
     * @param array $viewData templating view data
     * @param array $context serialization context
     * @param string|null $url custom url
     */
    public function render(
        string $component,
        array $props = [],
        array $viewData = [],
        ?string $url = null
    ): HTTPResponse {
        $viewData = array_merge($this->sharedViewData, $viewData);
        $props = array_merge($this->sharedProps, $props);
        $request = $this->getRequest();
        $url ??= '/' . $request->getURL();

        $only = array_filter(explode(',', $request->getHeader('X-Inertia-Partial-Data') ?? ''));
        $props = $only && $request->getHeader('X-Inertia-Partial-Component') === $component
            ? self::array_only($props, $only)
            : array_filter($props, static function ($prop) {
                return !($prop instanceof LazyProp);
            });

        array_walk_recursive($props, function (&$prop): void {
            if ($prop instanceof LazyProp) {
                $prop = call_user_func($prop);
            } elseif ($prop instanceof Closure) {
                $prop = $prop();
            }
        });

        $version = $this->getVersion();
        $page = json_encode(compact('component', 'props', 'url', 'version'));

        if ($request->getHeader('X-Inertia')) {
            return HTTPResponse::create()
                ->addHeader('Vary', 'Accept')
                ->addHeader('X-Inertia', true)
                ->addHeader('Content-Type', 'application/json')
                ->addHeader('Accept', 'application/json')
                ->setBody($page);
        }

        return HTTPResponse::create()
            ->setBody(Controller::curr()->renderWith(
                $this->getRootView(),
                ['PageData' => $page, 'ViewData' => ArrayData::create($viewData)]
            ));
    }

    private function getRequest(): HTTPRequest
    {
        return Controller::curr()->getRequest();
    }

    private static function array_only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }
}
