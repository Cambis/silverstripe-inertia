<?php

namespace Cambis\Inertia\Control\Middleware;

use Cambis\Inertia\Inertia;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use function file_exists;
use function in_array;
use function is_string;
use function md5;
use function md5_file;

class InertiaMiddleware implements HTTPMiddleware
{
    use Injectable;

    public function version(HTTPRequest $request): ?string
    {
        /** @var Inertia $inertia */
        $inertia = Injector::inst()->get(Inertia::class);

        if ($inertia->getAssetURL()) {
            return md5($inertia->getAssetURL());
        }

        if (!$inertia->getManifestFile()) {
            return null;
        }

        $manifestPath = Controller::join_links(Director::baseFolder(), $inertia->getManifestFile());
        $manifestFileMd5 = '';

        if (file_exists($manifestPath)) {
            $manifestFileMd5 = md5_file($manifestPath);
        }

        if (!is_string($manifestFileMd5)) {
            return null;
        }

        return $manifestFileMd5;
    }

    public function process(HTTPRequest $request, callable $delegate)
    {
        /** @var Inertia $inertia */
        $inertia = Injector::inst()->get(Inertia::class);

        $inertia->version(function () use ($request) {
            return $this->version($request);
        });

        /** @var HTTPResponse $response */
        $response = $delegate($request);

        if (!$request->getHeader('X-Inertia')) {
            return $response;
        }

        if ($request->isGET() && (string) $request->getHeader('X-Inertia-Version') !== $inertia->getVersion()) {
            return HTTPResponse::create()
                ->setStatusCode(409)
                ->addHeader('X-Inertia-Location', $request->getURL());
        }

        if ($response->getStatusCode() === 302 && in_array($request->httpMethod(), ['PUT', 'PATCH', 'DELETE'])) {
            $response->setStatusCode(303);
        }

        return $response;
    }
}
