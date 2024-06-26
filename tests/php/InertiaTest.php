<?php

namespace Cambis\Inertia\Tests;

use ArrayObject;
use Cambis\Inertia\Inertia;
use DateTime;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\View\SSViewer;
use stdClass;
use function http_build_query;
use function json_decode;
use function strlen;
use function substr;
use const FRAMEWORK_DIR;
use const JSON_THROW_ON_ERROR;

final class InertiaTest extends FunctionalTest
{
    /**
     * @var string[]
     * @phpstan-ignore-next-line
     */
    protected static $extra_controllers = [
        TestController::class,
    ];

    private Inertia $inertia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inertia = Injector::inst()->get(Inertia::class);

        Director::config()->set('alternate_base_url', '/');

        $themeDir = substr(__DIR__, strlen(FRAMEWORK_DIR)) . '/InertiaTest/';
        $themes = [
            'cambis/silverstripe-inertia:' . $themeDir,
            SSViewer::DEFAULT_THEME,
        ];
        SSViewer::set_themes($themes);

        Config::modify()->set(Inertia::class, 'root_view', TestController::class);
    }

    public function testResponse(): void
    {
        $response = $this->get('TestController');

        $this->assertSame($response->getStatusCode(), 200);
        $this->assertNull($response->getHeader('X-Inertia'));
        $this->assertSame('text/html; charset=utf-8', $response->getHeader('Content-Type'));
        $this->assertStringContainsString(
            "<div id='app' data-page='{&quot;component&quot;:&quot;Dashboard&quot;,&quot;props&quot;:[],"
            . "&quot;url&quot;:&quot;\/TestController&quot;,&quot;version&quot;:&quot;&quot;}'></div>",
            $response->getBody()
        );
    }

    public function testJsonResponse(): void
    {
        $response = $this->get('TestController', null, [
            'X-Inertia' => 'true',
        ]);

        $this->assertSame($response->getStatusCode(), 200);
        $this->assertNotNull($response->getHeader('X-Inertia'));
        $this->assertSame('application/json', $response->getHeader('Accept'));
        $this->assertSame('application/json', $response->getHeader('Content-Type'));
    }

    public function testProps(): void
    {
        $params = http_build_query([
            'props' => [
                'foo' => 'bar',
            ],
        ]);
        $response = $this->get('TestController?' . $params, null, [
            'X-Inertia' => 'true',
        ]);
        $data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame([
            'foo' => 'bar',
        ], $data['props']);
    }

    public function testSharedProps(): void
    {
        $this->inertia->share('baz', 'foobar');

        $params = http_build_query([
            'props' => [
                'foo' => 'bar',
            ],
        ]);
        $response = $this->get('TestController?' . $params, null, [
            'X-Inertia' => 'true',
        ]);
        $data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('foobar', $this->inertia->getShared('baz'));
        $this->assertSame([
            'baz' => 'foobar',
        ], $this->inertia->getShared());
        $this->assertSame([
            'baz' => 'foobar',
            'foo' => 'bar',
        ], $data['props']);
    }

    public function testClosureProps(): void
    {
        $this->inertia->share('foo', static function (): string {
            return 'bar';
        });

        $response = $this->get('TestController', null, [
            'X-Inertia' => 'true',
        ]);
        $data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame([
            'foo' => 'bar',
        ], $data['props']);
    }

    public function testLazyProps(): void
    {
        $this->inertia->share('foo', $this->inertia->lazy(static function (): string {
            return 'bar';
        }));

        $response = $this->get('TestController', null, [
            'X-Inertia' => 'true',
        ]);
        $data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame([], $data['props']);
    }

    public function testPartialData(): void
    {
        $this->inertia->share('foo', $this->inertia->lazy(static function (): string {
            return 'bar';
        }));

        $this->inertia->share('baz', 'foobar');

        $response = $this->get(
            'TestController',
            null,
            [
                'X-Inertia' => 'true',
                'X-Inertia-Partial-Data' => 'foo',
                'X-Inertia-Partial-Component' => 'Dashboard',
            ]
        );

        $data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame([
            'foo' => 'bar',
        ], $data['props']);
    }

    public function testViewData(): void
    {
        $this->inertia->viewData('foo', 'bar');

        $this->assertSame('bar', $this->inertia->getViewData('foo'));
        $this->assertSame([
            'foo' => 'bar',
        ], $this->inertia->getViewData());
    }

    public function testVersion(): void
    {
        $this->assertEmpty($this->inertia->getVersion());

        $this->inertia->version('foo');

        $this->assertSame('foo', $this->inertia->getVersion());
    }

    public function testTypesArePreserved(): void
    {
        $props = [
            'integer' => 123,
            'float' => 1.23,
            'string' => 'foo',
            'null' => null,
            'true' => true,
            'false' => false,
            'object' => new DateTime(),
            'empty_object' => new stdClass(),
            'iterable_object' => new ArrayObject([1, 2, 3]),
            'empty_iterable_object' => new ArrayObject(),
            'array' => [1, 2, 3],
            'empty_array' => [],
            'associative_array' => [
                'foo' => 'bar',
            ],
        ];

        foreach ($props as $key => $value) {
            $this->inertia->share($key, $value);
        }

        $response = $this->get('TestController', null, [
            'X-Inertia' => 'true',
        ]);
        $data = json_decode($response->getBody(), false, 512, JSON_THROW_ON_ERROR);
        $responseProps = (array) $data->props;

        $this->assertIsInt($responseProps['integer']);
        $this->assertIsFloat($responseProps['float']);
        $this->assertIsString($responseProps['string']);
        $this->assertNull($responseProps['null']);
        $this->assertTrue($responseProps['true']);
        $this->assertFalse($responseProps['false']);
        $this->assertIsObject($responseProps['object']);
        $this->assertIsObject($responseProps['empty_object']);
        $this->assertIsObject($responseProps['iterable_object']);
        $this->assertIsObject($responseProps['empty_iterable_object']);
        $this->assertIsArray($responseProps['array']);
        $this->assertIsArray($responseProps['empty_array']);
        $this->assertIsObject($responseProps['associative_array']);
    }

    public function testLocationResponseDefault(): void
    {
        $response = $this->inertia->location('foo');

        $this->assertSame($response->getStatusCode(), 302);
        $this->assertSame($response->getHeader('location'), 'foo');
    }

    public function testLocationResponseRedirect(): void
    {
        $url = HTTPResponse::create()
            ->addHeader('location', 'foo')
            ->setStatusCode(302);

        $response = $this->inertia->location($url);

        $this->assertSame($response->getStatusCode(), 302);
        $this->assertSame($response->getHeader('location'), 'foo');
    }

    public function testLocationResponseConflict(): void
    {
        $request = (new HTTPRequest('GET', '/'))
            ->addHeader('X-Inertia', 'true');

        Controller::curr()->setRequest($request);
        $response = $this->inertia->location('foo');

        $this->assertSame($response->getStatusCode(), 409);
        $this->assertSame($response->getHeader('X-Inertia-Location'), 'foo');
    }
}
