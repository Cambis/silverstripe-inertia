<?php

namespace Cambis\Inertia\SSR;

use SilverStripe\Core\Injector\Injectable;

class Response
{
    use Injectable;

    public string $head;

    public string $body;

    public function __construct(string $head, string $body)
    {
        $this->head = $head;
        $this->body = $body;
    }
}
