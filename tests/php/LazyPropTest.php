<?php

namespace Cambis\Inertia\Tests;

use Cambis\Inertia\LazyProp;
use SilverStripe\Dev\SapphireTest;

final class LazyPropTest extends SapphireTest
{
    public function testInvoke(): void
    {
        $lazyProp = LazyProp::create(static function () {
            return 'A lazy value';
        });

        $this->assertSame('A lazy value', $lazyProp());
    }
}
