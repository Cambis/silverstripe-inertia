<?php

namespace Cambis\Inertia;

use SilverStripe\Core\Injector\Injectable;
use function call_user_func;

class LazyProp
{
    use Injectable;

    /**
     * @var callable(): mixed
     */
    private $callback;

    /**
     * @param callable(): mixed $callback
     */
    public function __construct(mixed $callback)
    {
        $this->callback = $callback;
    }

    public function __invoke(): mixed
    {
        return call_user_func($this->callback);
    }
}
