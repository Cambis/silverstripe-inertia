<?php

namespace Cambis\Inertia\Extension;

use Cambis\Inertia\Inertia;
use SilverStripe\ORM\DataExtension;

/**
 * @property \Cambis\Inertia\Inertia $inertia
 */
class InertiaPageControllerExtension extends DataExtension
{
    private static array $dependencies = [
        'inertia' => '%$' . Inertia::class,
    ];
}
