<?php

namespace Cambis\Inertia\SSR;

use SilverStripe\Core\Injector\Injectable;

class Response
{
    /**
     * @readonly
     */
    public string $head;
    /**
     * @readonly
     */
    public string $body;
    use Injectable;

    public function __construct(string $head, string $body)
    {
        $this->head = $head;
        $this->body = $body;
    }
}
