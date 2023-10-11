<?php

namespace Cambis\Inertia\Control\Middleware;

use Cambis\Inertia\Inertia;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;

class InertiaMiddleware implements HTTPMiddleware
{
    use Injectable;

    public function version(?string $manifestFile): ?string
    {
        if (!$manifestFile) {
            return null;
        }

        $manifestPath = BASE_PATH . $manifestFile;
        $manifestFileMd5 = '';

        if (file_exists($manifestPath)) {
            $manifestFileMd5 = md5_file($manifestPath);
        }

        if (!is_string($manifestFileMd5)) {
            return null;
        }

        return $manifestFileMd5;
    }

    /**
     * @inheritDoc
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        /** @var Inertia $inertia */
        $inertia = Injector::inst()->get(Inertia::class);

        $inertia->version(function () use ($inertia) {
            return $this->version($inertia->getManifestFile());
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
