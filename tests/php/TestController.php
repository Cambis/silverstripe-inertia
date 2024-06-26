<?php

namespace Cambis\Inertia\Tests;

use Cambis\Inertia\Extension\InertiaPageControllerExtension;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Dev\TestOnly;

/**
 * @mixin InertiaPageControllerExtension
 */
final class TestController extends Controller implements TestOnly
{
    private static string $url_segment = 'TestController';

    /**
     * @var array<class-string>
     */
    private static array $extensions = [
        InertiaPageControllerExtension::class,
    ];

    public function index(HTTPRequest $request): HTTPResponse
    {
        $props = $request->getVar('props') ?? [];
        $viewData = $request->getVar('viewData') ?? [];

        return $this->inertia->render('Dashboard', (array) $props, (array) $viewData);
    }
}
