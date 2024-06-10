<?php

namespace Cambis\Inertia\Extension;

use AllowDynamicProperties;
use Cambis\Inertia\Inertia;
use SilverStripe\Core\Extension;

/**
 * @property Inertia $inertia
 */
#[AllowDynamicProperties]
class InertiaPageControllerExtension extends Extension
{
    /**
     * @config
     */
    private static array $dependencies = [
        'inertia' => '%$' . Inertia::class,
    ];
}
