<?php

namespace Cambis\Inertia;

use SilverStripe\Core\Injector\Injectable;
use function call_user_func;

/**
 * @see \Cambis\Inertia\Tests\LazyPropTest
 */
readonly class LazyProp
{
    use Injectable;

    public function __construct(
        /**
         * @var callable(): mixed
         */
        private mixed $callback
    ) {
    }

    public function __invoke(): mixed
    {
        return call_user_func($this->callback);
    }
}
