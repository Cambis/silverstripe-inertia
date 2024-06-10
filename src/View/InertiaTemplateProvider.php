<?php

namespace Cambis\Inertia\View;

use Cambis\Inertia\Inertia;
use Cambis\Inertia\SSR\HTTPGateway;
use Cambis\Inertia\SSR\Response;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\View\TemplateGlobalProvider;
use function sprintf;

class InertiaTemplateProvider implements TemplateGlobalProvider
{
    /**
     * @return array<string, array<string, string>|string>
     */
    public static function get_template_global_variables(): array
    {
        return [
            'Inertia' => [
                'method' => 'inertia_body',
                'casting' => 'HTMLFragment',
            ],
            'InertiaBody' => [
                'method' => 'inertia_body',
                'casting' => 'HTMLFragment',
            ],
            'InertiaHead' => [
                'method' => 'inertia_head',
                'casting' => 'HTMLFragment',
            ],
            'IsSSR' => 'is_ssr',
        ];
    }

    public static function inertia_head(string $page): string
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

    public static function inertia_body(string $page): string
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

    public static function is_ssr(): bool
    {
        /** @var Inertia $inertia */
        $inertia = Injector::inst()->get(Inertia::class);

        return $inertia->isSsr();
    }
}
