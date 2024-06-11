<?php

namespace Cambis\Inertia;

use SilverStripe\Core\Injector\Injectable;
use function call_user_func;

/**
 * @see \Cambis\Inertia\Tests\LazyPropTest
 */
class LazyProp
{
    /**
     * @readonly
     * @var mixed
     */
    private $callback;
    use Injectable;

    /**
     * @param mixed $callback
     */
    public function __construct($callback)
    {
        /**
         * @var callable(): mixed
         */
        $this->callback = $callback;
    }

    /**
     * @return mixed
     */
    public function __invoke()
    {
        return call_user_func($this->callback);
    }
}
