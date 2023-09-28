<?php

namespace Cambis\Inertia\Tests\Extension;

use Cambis\Inertia\Inertia;
use Cambis\Inertia\Tests\TestController;
use SilverStripe\Dev\SapphireTest;

/**
 * @property TestController $controller
 */
class InertiaPageControllerExtensionTest extends SapphireTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = TestController::create();
        $this->controller->doInit();
    }

    public function testDependencies(): void
    {
        $this->assertInstanceOf(Inertia::class, $this->controller->inertia);
    }
}
