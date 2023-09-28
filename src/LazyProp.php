<?php

namespace Cambis\Inertia;

use SilverStripe\Core\Injector\Injectable;

class LazyProp
{
    use Injectable;

    /** @var callable|string|array */
    private mixed $callback;

    /**
     * @param callable|string|array $callback
     */
    public function __construct(mixed $callback)
    {
        $this->callback = $callback;
    }

    public function __invoke()
    {
        return call_user_func($this->callback);
    }
}
