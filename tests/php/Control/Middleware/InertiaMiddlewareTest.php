<?php

namespace Cambis\Inertia\Tests\Control\Middleware;

use Cambis\Inertia\Control\Middleware\InertiaMiddleware;
use Cambis\Inertia\Inertia;
use PHPUnit\Framework\MockObject\MockObject;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;

class InertiaMiddlewareTest extends SapphireTest
{
    protected Inertia $inertia;

    /**
     * @var InertiaMiddleware&MockObject
     */
    protected $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inertia = Injector::inst()->get(Inertia::class);
        $this->middleware = $this->getMockBuilder(InertiaMiddleware::class)
            ->setMethods(['version'])
            ->getMock();

        $this->middleware->expects($this->any())->method('version')->willReturn('foo');
    }

    public function testPassThroughResponse(): void
    {
        $request = new HTTPRequest('GET', '/');
        $response = HTTPResponse::create();

        $delegate = static function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->process($request, $delegate);

        $this->assertInstanceOf(HTTPResponse::class, $result);
        $this->assertSame($result->getStatusCode(), 200);
        $this->assertNull($result->getHeader('X-Inertia'));
    }

    public function testInertiaResponse(): void
    {
        $this->middleware->expects($this->once())->method('version');

        $request = (new HTTPRequest('GET', '/'))
            ->addHeader('X-Inertia', 'true')
            ->addHeader('X-Inertia-Version', 'foo');

        $response = HTTPResponse::create();

        $delegate = static function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->process($request, $delegate);

        $this->assertInstanceOf(HTTPResponse::class, $result);
        $this->assertSame($result->getStatusCode(), 200);
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
            ->addHeader('X-Inertia-Version', 'foo');

        $response = HTTPResponse::create()
            ->setStatusCode(302);

        $delegate = static function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->process($request, $delegate);

        $this->assertInstanceOf(HTTPResponse::class, $result);
        $this->assertSame($result->getStatusCode(), 303);
    }

    public function testInertiaVersion(): void
    {
        $this->middleware->expects($this->once())->method('version');

        $request = (new HTTPRequest('GET', '/'))
            ->addHeader('X-Inertia', 'true')
            ->addHeader('X-Inertia-Version', 'bar');

        $response = HTTPResponse::create();

        $delegate = static function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->process($request, $delegate);

        $this->assertInstanceOf(HTTPResponse::class, $result);
        $this->assertSame(409, $result->getStatusCode());
        $this->assertSame($request->getUrl(), $result->getHeader('X-Inertia-Location'));
    }
}
