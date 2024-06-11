<?php

namespace Cambis\Inertia\Extension;

use AllowDynamicProperties;
use Cambis\Inertia\Inertia;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Extension;

/**
 * @property Inertia $inertia
 * @extends Extension<Controller>
 */
#[AllowDynamicProperties]
class InertiaPageControllerExtension extends Extension
{
    /**
     * @var string[]
     */
    private static array $dependencies = [
        'inertia' => '%$' . Inertia::class,
    ];
}
