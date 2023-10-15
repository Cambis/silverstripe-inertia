<?php

namespace Cambis\Inertia\Tests\Control\Middleware;

use Cambis\Inertia\Control\Middleware\InertiaMiddleware;
use Cambis\Inertia\Inertia;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;

class InertiaMiddlewareTest extends SapphireTest
{
    protected Inertia $inertia;

    protected InertiaMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inertia = Injector::inst()->get(Inertia::class);
        Config::modify()->set(Inertia::class, 'manifest_file', '/tests/php/InertiaTest/test-manifest.json');
        $this->middleware = InertiaMiddleware::create();
    }

    public function testPassThroughResponse(): void
    {
        $request = new HTTPRequest('GET', '/');
        $response = HTTPResponse::create();

        $delegate = static function (HTTPRequest $request) use ($response): HTTPResponse {
            return $response;
        };

        $result = $this->middleware->process($request, $delegate);

        $this->assertInstanceOf(HTTPResponse::class, $result);
        $this->assertSame($result->getStatusCode(), 200);
        $this->assertNull($result->getHeader('X-Inertia'));
    }

    public function testInertiaResponse(): void
    {
        $request = (new HTTPRequest('GET', '/'))
            ->addHeader('X-Inertia', 'true')
            ->addHeader('X-Inertia-Version', 'd41d8cd98f00b204e9800998ecf8427e');

        $response = HTTPResponse::create();

        $delegate = static function (HTTPRequest $request) use ($response): HTTPResponse {
            return $response;
        };

        $result = $this->middleware->process($request, $delegate);

        $this->assertInstanceOf(HTTPResponse::class, $result);
        $this->assertSame($result->getStatusCode(), 200);
        $this->assertSame($this->inertia->getVersion(), 'd41d8cd98f00b204e9800998ecf8427e');
    }

    /**
     * @return array<array<string>>
     */
    public function inertiaRedirectProvider(): array
    {
        return [
            ['PUT'],
            ['PATCH'],
            ['DELETE'],
        ];
    }

    /**
     * @dataProvider inertiaRedirectProvider
     */
    public function testInertiaRedirect(string $httpMethod): void
    {
        $request = (new HTTPRequest($httpMethod, '/'))
            ->addHeader('X-Inertia', 'true')
            ->addHeader('X-Inertia-Version', 'd41d8cd98f00b204e9800998ecf8427e');

        $response = HTTPResponse::create()
            ->setStatusCode(302);

        $delegate = static function (HTTPRequest $request) use ($response): HTTPResponse {
            return $response;
        };

        $result = $this->middleware->process($request, $delegate);

        $this->assertInstanceOf(HTTPResponse::class, $result);
        $this->assertSame($result->getStatusCode(), 303);
        $this->assertSame($this->inertia->getVersion(), 'd41d8cd98f00b204e9800998ecf8427e');
    }

    public function testInertiaVersionManifest(): void
    {
        $request = (new HTTPRequest('GET', '/'))
            ->addHeader('X-Inertia', 'true')
            ->addHeader('X-Inertia-Version', 'busted');

        $response = HTTPResponse::create();

        $delegate = static function (HTTPRequest $request) use ($response): HTTPResponse {
            return $response;
        };

        $result = $this->middleware->process($request, $delegate);

        $this->assertInstanceOf(HTTPResponse::class, $result);
        $this->assertSame(409, $result->getStatusCode());
        $this->assertSame($request->getUrl(), $result->getHeader('X-Inertia-Location'));
        $this->assertSame($this->inertia->getVersion(), 'd41d8cd98f00b204e9800998ecf8427e');
    }

    public function testInertiaVersionNoManifest(): void
    {
        Config::modify()->set(Inertia::class, 'manifest_file', null);

        $request = (new HTTPRequest('GET', '/'))
            ->addHeader('X-Inertia', 'true');

        $response = HTTPResponse::create();

        $delegate = static function (HTTPRequest $request) use ($response): HTTPResponse {
            return $response;
        };

        $this->middleware->process($request, $delegate);

        $this->assertEmpty($this->inertia->getVersion());
    }

    public function testInertiaVersionNonExistentManifest(): void
    {
        Config::modify()->set(Inertia::class, 'manifest_file', '/this/file/does/not/exist.json');

        $request = (new HTTPRequest('GET', '/'))
            ->addHeader('X-Inertia', 'true');

        $response = HTTPResponse::create();

        $delegate = static function (HTTPRequest $request) use ($response): HTTPResponse {
            return $response;
        };

        $this->middleware->process($request, $delegate);

        $this->assertEmpty($this->inertia->getVersion());
    }
}
