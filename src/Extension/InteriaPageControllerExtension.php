<?php

namespace Cambis\Inertia\Extension;

use Cambis\Inertia\Inertia;
use SilverStripe\Core\Extension;

/**
 * @property Inertia $inertia
 */
class InertiaPageControllerExtension extends Extension
{
    private static array $dependencies = [
        'inertia' => '%$' . Inertia::class,
    ];
}
