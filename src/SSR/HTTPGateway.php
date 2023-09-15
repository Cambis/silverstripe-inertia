<?php

namespace Cambis\Inertia\SSR;

use Cambis\Inertia\Inertia;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;

class HTTPGateway
{
    use Injectable;

    protected ?Client $client;

    public function __construct(?Client $client = null)
    {
        /** @var Inertia $inertia */
        $inertia = Injector::inst()->get(Inertia::class);

        if (is_null($client)) {
            $client = new Client(['base_uri' => $inertia->getSsrHost()]);
        }

        $this->client = $client;
    }

    public function dispatch(string $page): ?Response
    {
        try {
            $response = $this->client->post(
                'render',
                [
                    'json' => json_decode(html_entity_decode($page)),
                ]
            );
        } catch (GuzzleException) {
            return null;
        }

        if (is_null($response)) {
            return null;
        }

        $content = json_decode($response->getBody()->getContents(), true);

        if (!$content || !is_array($content)) {
            return null;
        }

        return Response::create(
            implode("\n", $content['head']),
            $content['body']
        );
    }
}
