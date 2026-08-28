<?php

declare(strict_types=1);

namespace MunicipioModularityModuleGroups\Tests;

use MunicipioModularityModuleGroups\Editor\MetaboxRenderer;
use MunicipioModularityModuleGroups\GroupNormalizer;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use WP_Post;

final class MetaboxRendererTest extends TestCase
{
    #[After]
    public function resetWordPressState(): void
    {
        global $testFilters;
        global $testPosts;

        $testFilters = [];
        $testPosts = [];
        \Modularity\ModuleManager::$available = [];
    }

    public function testItRendersSidebarIncompatibilityAsJson(): void
    {
        global $testFilters;
        global $testPosts;

        $testPosts[42] = new WP_Post(42, 'mod-text', 'Contact');
        \Modularity\ModuleManager::$available = [
            'mod-text' => ['labels' => ['name' => 'Text']],
        ];
        $testFilters['Modularity/Editor/SidebarIncompability'] = static function (array $moduleSpecification): array {
            $moduleSpecification['sidebar_incompability'] = ['slider-area', 'footer-area'];

            return $moduleSpecification;
        };

        $markup = $this->renderModule(['postid' => 42]);

        static::assertStringContainsString(
            'data-sidebar-incompability="[&quot;slider-area&quot;,&quot;footer-area&quot;]"',
            $markup,
        );
    }

    public function testItRendersAnEmptyJsonArrayWithoutRestrictions(): void
    {
        global $testPosts;

        $testPosts[42] = new WP_Post(42, 'mod-text', 'Contact');
        \Modularity\ModuleManager::$available = [
            'mod-text' => ['labels' => ['name' => 'Text']],
        ];

        static::assertStringContainsString('data-sidebar-incompability="[]"', $this->renderModule(['postid' => 42]));
    }

    /**
     * @param array<string, mixed> $row
     */
    private function renderModule(array $row): string
    {
        $renderer = new MetaboxRenderer(new GroupNormalizer());
        $method = new ReflectionMethod($renderer, 'renderModule');

        ob_start();
        $method->invoke($renderer, 'content-area', 'row-key', $row, 'transparent');

        return (string) ob_get_clean();
    }
}
