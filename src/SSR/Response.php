<?php

namespace Cambis\Inertia\SSR;

use SilverStripe\Core\Injector\Injectable;

readonly class Response
{
    use Injectable;

    public function __construct(
        public string $head,
        public string $body
    ) {
    }
}
