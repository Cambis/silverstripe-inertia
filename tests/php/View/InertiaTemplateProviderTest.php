<?php

namespace Cambis\Inertia\Tests\View;

use Cambis\Inertia\View\InertiaTemplateProvider;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\SSViewer;

class InertiaTemplateProviderTest extends SapphireTest
{
    public function templateGlobalVariablesProvider(): array
    {
        return [
            ['Inertia'],
            ['InertiaBody'],
            ['InertiaHead'],
            ['IsSSR'],
        ];
    }

    /**
     * @dataProvider templateGlobalVariablesProvider
     */
    public function testTemplateGlobalVariables(string $key): void
    {
        $this->assertArrayHasKey(
            $key,
            InertiaTemplateProvider::get_template_global_variables()
        );
    }

    public function testInertiaBody(): void
    {
        $template = SSViewer::execute_string(
            "\$InertiaBody('')",
            ''
        );

        $this->assertSame("<div id='app' data-page=''></div>", $template);
    }
}
