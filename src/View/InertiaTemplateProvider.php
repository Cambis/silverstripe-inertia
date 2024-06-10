<?php

namespace Cambis\Inertia\View;

use Cambis\Inertia\Inertia;
use Cambis\Inertia\SSR\HTTPGateway;
use Cambis\Inertia\SSR\Response;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\View\TemplateGlobalProvider;
use function sprintf;

/**
 * @see \Cambis\Inertia\Tests\View\InertiaTemplateProviderTest
 */
class InertiaTemplateProvider implements TemplateGlobalProvider
{
    /**
     * @return array<string, array<string, string>|string>
     */
    public static function get_template_global_variables(): array
    {
        return [
            'Inertia' => [
                'method' => 'inertiaBody',
                'casting' => 'HTMLFragment',
            ],
            'InertiaBody' => [
                'method' => 'inertiaBody',
                'casting' => 'HTMLFragment',
            ],
            'InertiaHead' => [
                'method' => 'inertiaHead',
                'casting' => 'HTMLFragment',
            ],
            'IsSSR' => 'isSsr',
        ];
    }

    public static function inertiaHead(string $page): string
    {
        /** @var Inertia $inertia */
        $inertia = Injector::inst()->get(Inertia::class);

        /** @var HTTPGateway $gateway */
        $gateway = Injector::inst()->get(HTTPGateway::class);

        if ($inertia->isSsr()) {
            $response = $gateway->dispatch($page);

            if ($response instanceof Response) {
                return $response->head;
            }
        }

        return '';
    }

    public static function inertiaBody(string $page): string
    {
        /** @var Inertia $inertia */
        $inertia = Injector::inst()->get(Inertia::class);

        /** @var HTTPGateway $gateway */
        $gateway = Injector::inst()->get(HTTPGateway::class);

        if ($inertia->isSsr()) {
            $response = $gateway->dispatch($page);

            if ($response instanceof Response) {
                return $response->body;
            }
        }

        return sprintf(
            "<div id='app' data-page='%s'></div>",
            $page
        );
    }

    public static function isSsr(): bool
    {
        /** @var Inertia $inertia */
        $inertia = Injector::inst()->get(Inertia::class);

        return $inertia->isSsr();
    }
}
