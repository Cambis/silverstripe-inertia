<?php

namespace Cambis\Inertia\Tests\View;

use Cambis\Inertia\Inertia;
use Cambis\Inertia\View\InertiaTemplateProvider;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\SSViewer;

class InertiaTemplateProviderTest extends SapphireTest
{
    /**
     * @return array<array<string>>
     */
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

    public function testInertiaHead(): void
    {
        $template = SSViewer::execute_string(
            "\$InertiaHead('')",
            ''
        );

        $this->assertEmpty($template);
    }

    public function testInertiaBody(): void
    {
        $template = SSViewer::execute_string(
            "\$InertiaBody('')",
            ''
        );

        $this->assertSame("<div id='app' data-page=''></div>", $template);
    }

    public function testIsSSR(): void
    {
        Config::modify()->set(Inertia::class, 'ssr_enabled', true);
        $this->assertTrue(InertiaTemplateProvider::isSsr());

        Config::modify()->set(Inertia::class, 'ssr_enabled', false);
        $this->assertFalse(InertiaTemplateProvider::isSsr());
    }
}
