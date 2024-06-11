<?php

namespace Cambis\Inertia\Extension;

use Cambis\Inertia\Inertia;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Extension;

/**
 * @extends Extension<Controller>
 */
class InertiaPageControllerExtension extends Extension
{
    public Inertia $inertia;

    /**
     * @var string[]
     */
    private static array $dependencies = [
        'inertia' => '%$' . Inertia::class,
    ];
}
