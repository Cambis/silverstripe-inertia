<?php

namespace Cambis\Inertia;

use Closure;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\View\ArrayData;
use function array_filter;
use function array_flip;
use function array_intersect_key;
use function array_walk_recursive;
use function call_user_func;
use function explode;
use function json_encode;
use const JSON_THROW_ON_ERROR;

class Inertia
{
    use Configurable;
    use Injectable;

    /**
     * @var array<string, mixed>
     */
    protected array $sharedProps = [];

    /**
     * @var array<string, mixed>
     */
    protected array $sharedViewData = [];

    /**
     * @var callable|string|null
     */
    protected mixed $version = null;

    /**
     * The root template of your application, defaults to 'Page'.
     */
    private static string $root_view = 'Page';

    /**
     * The location of your external manifest file, used for versioning.
     */
    private static ?string $asset_url = null;

    /**
     * The location of your local manifest file, used for versioning. Must include a leading slash.
     */
    private static ?string $manifest_file = null;

    /**
     * True if using SSR.
     */
    private static bool $ssr_enabled = false;

    /**
     * The location of the SSR host.
     */
    private static string $ssr_host = 'http://127.0.0.1:13714';

    public function share(string $key, mixed $value = null): void
    {
        $this->sharedProps[$key] = $value;
    }

    public function getShared(?string $key = null): mixed
    {
        if ($key !== null) {
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
        if ($key !== null) {
            return $this->sharedViewData[$key] ?? null;
        }

        return $this->sharedViewData;
    }

    /**
     * @param callable|string|null $version
     */
    public function version(mixed $version): void
    {
        $this->version = $version;
    }

    public function getRootView(): string
    {
        return static::config()->get('root_view');
    }

    public function getAssetURL(): ?string
    {
        return static::config()->get('asset_url');
    }

    public function getManifestFile(): ?string
    {
        return static::config()->get('manifest_file');
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
        return (bool) static::config()->get('ssr_enabled');
    }

    public function getSsrHost(): string
    {
        return static::config()->get('ssr_host');
    }

    /**
     * @param callable(): mixed $callback
     */
    public function lazy(callable $callback): LazyProp
    {
        return LazyProp::create($callback);
    }

    public function location(HTTPResponse|string $url): HTTPResponse
    {
        if ($url instanceof HTTPResponse && $url->isRedirect() && $url->getHeader('location') !== null) {
            $url = $url->getHeader('location');
        }

        if ($this->getRequest()->getHeader('X-Inertia') !== null) {
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
     *     return $this->inertia->render('Page', ['propName' => 'value']);
     * }
     * ```
     *
     * @see https://inertiajs.com/responses
     * @param string $component component name
     * @param array<string, mixed> $props component properties
     * @param array<string, mixed> $viewData templating view data
     * @param string|null $url custom url
     */
    public function render(
        string $component,
        array $props = [],
        array $viewData = [],
        ?string $url = null
    ): HTTPResponse {
        $viewData = [...$this->sharedViewData, ...$viewData];
        $props = [...$this->sharedProps, ...$props];
        $request = $this->getRequest();
        $url ??= '/' . $request->getURL();
        $only = array_filter(explode(',', $request->getHeader('X-Inertia-Partial-Data') ?? ''));

        $props = $only !== [] && $request->getHeader('X-Inertia-Partial-Component') === $component
            ? self::array_only($props, $only)
            : array_filter($props, static function ($prop): bool {
                return !($prop instanceof LazyProp);
            });

        array_walk_recursive($props, static function (&$prop): void {
            if ($prop instanceof LazyProp) {
                $prop = call_user_func($prop);
            } elseif ($prop instanceof Closure) {
                $prop = $prop();
            }
        });

        $version = $this->getVersion();
        $page = json_encode([
            'component' => $component,
            'props' => $props,
            'url' => $url,
            'version' => $version,
        ], JSON_THROW_ON_ERROR);

        if ($request->getHeader('X-Inertia') !== null) {
            return HTTPResponse::create()
                ->addHeader('Vary', 'Accept')
                ->addHeader('X-Inertia', 'true')
                ->addHeader('Content-Type', 'application/json')
                ->addHeader('Accept', 'application/json')
                ->setBody($page);
        }

        return HTTPResponse::create()
            ->setBody(Controller::curr()->renderWith(
                $this->getRootView(),
                [
                    'PageData' => $page,
                    'ViewData' => ArrayData::create($viewData),
                ]
            ));
    }

    private function getRequest(): HTTPRequest
    {
        return Controller::curr()->getRequest();
    }

    /**
     * @param array<string, mixed> $array
     * @param array<string> $keys
     * @return array<string, mixed>
     */
    private function array_only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }
}
