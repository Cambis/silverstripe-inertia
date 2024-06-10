<?php

namespace Cambis\Inertia\SSR;

use Cambis\Inertia\Inertia;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use function html_entity_decode;
use function implode;
use function is_array;
use function json_decode;
use const JSON_THROW_ON_ERROR;

class HTTPGateway
{
    use Injectable;

    protected ?Client $client;

    public function __construct(?Client $client = null)
    {
        /** @var Inertia $inertia */
        $inertia = Injector::inst()->get(Inertia::class);

        if ($client === null) {
            $client = new Client([
                'base_uri' => $inertia->getSsrHost(),
            ]);
        }

        $this->client = $client;
    }

    public function dispatch(string $page): ?Response
    {
        if (!$this->client instanceof Client) {
            return null;
        }

        try {
            $response = $this->client->post(
                'render',
                [
                    'json' => json_decode(html_entity_decode($page), null, 512, JSON_THROW_ON_ERROR),
                ]
            );
        } catch (GuzzleException) {
            return null;
        }

        $content = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        if (!$content || !is_array($content)) {
            return null;
        }

        return Response::create(
            implode("\n", $content['head']),
            $content['body']
        );
    }
}
