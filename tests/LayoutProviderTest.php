<?php

declare(strict_types=1);

namespace MunicipioModularityModuleGroups\Tests;

use MunicipioModularityModuleGroups\Frontend\LayoutProvider;
use MunicipioModularityModuleGroups\GroupNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TestModuleManager;
use WP_Post;

final class LayoutProviderTest extends TestCase
{
    #[DataProvider('layouts')]
    public function testContentGroupsRespectThePageTemplate(string $template, array $sidebars, bool $grouped): void
    {
        $GLOBALS['post'] = new WP_Post(2, 'page', 'Start');
        $GLOBALS['testTemplate'] = $template;
        $GLOBALS['testSidebars'] = $sidebars;
        $GLOBALS['testPosts'] = [79 => new WP_Post(79, 'mod-test', 'Module')];
        TestModuleManager::$available = ['mod-test' => true];
        $rows = [['postid' => 79, 'background' => 'secondary', 'hidden' => false]];
        $GLOBALS['testMeta'] = [
            2 => ['modularity-modules' => [
                'slider-area' => $rows,
                'content-area' => $rows,
                'left-sidebar' => $rows,
                'right-sidebar' => $rows,
            ]],
        ];

        $plan = (new LayoutProvider(new GroupNormalizer()))->plan();

        static::assertArrayHasKey('slider-area', $plan);
        static::assertArrayNotHasKey('left-sidebar', $plan);
        static::assertArrayNotHasKey('right-sidebar', $plan);
        static::assertSame($grouped, isset($plan['content-area']));
        if ($grouped) {
            static::assertSame(
                [
                    ['postid' => 79, 'background' => 'secondary', 'opens' => true, 'closes' => true],
                ],
                $plan['content-area'],
            );
        }
    }

    public static function layouts(): iterable
    {
        yield 'one-page with global widgets' => [
            'one-page.blade.php',
            ['left-sidebar' => true, 'right-sidebar' => true],
            true,
        ];
        yield 'one-page without widgets' => ['one-page.blade.php', [], true];
        yield 'default with left sidebar' => ['', ['left-sidebar' => true], false];
        yield 'default with right sidebar' => ['', ['right-sidebar' => true], false];
        yield 'default without sidebars' => ['', [], true];
        yield 'centered page with left sidebar' => ['page-centered.blade.php', ['left-sidebar' => true], false];
        yield 'other template with widgets' => ['full-width.blade.php', ['left-sidebar' => true], false];
    }

    protected function tearDown(): void
    {
        foreach (['post', 'testTemplate', 'testSidebars', 'testPosts', 'testMeta'] as $key) {
            unset($GLOBALS[$key]);
        }
        TestModuleManager::$available = [];
    }
}
