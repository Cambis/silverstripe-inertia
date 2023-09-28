<?php

namespace Cambis\Inertia\Tests;

use Cambis\Inertia\LazyProp;
use SilverStripe\Dev\SapphireTest;

class LazyPropTest extends SapphireTest
{
    public function testInvoke(): void
    {
        $lazyProp = LazyProp::create(function () {
            return 'A lazy value';
        });

        $this->assertSame('A lazy value', $lazyProp());
    }
}
