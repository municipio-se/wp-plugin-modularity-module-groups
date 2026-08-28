<?php

declare(strict_types=1);

namespace MunicipioModularityModuleGroups\Tests;

use MunicipioModularityModuleGroups\Frontend\LayoutProvider;
use MunicipioModularityModuleGroups\Frontend\Renderer;
use MunicipioModularityModuleGroups\GroupNormalizer;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use WP_Post;

final class RendererTest extends TestCase
{
    public function testRepeatedModulesKeepBalancedGroupWrappersWithWarmFragmentCache(): void
    {
        $plan = [
            ['postid' => 53, 'background' => 'transparent', 'opens' => true, 'closes' => false],
            ['postid' => 51, 'background' => 'transparent', 'opens' => false, 'closes' => false],
            ['postid' => 11_049, 'background' => 'transparent', 'opens' => false, 'closes' => true],
            ['postid' => 65, 'background' => 'neutral', 'opens' => true, 'closes' => true],
            ['postid' => 11_049, 'background' => 'transparent', 'opens' => true, 'closes' => false],
            ['postid' => 11_229, 'background' => 'transparent', 'opens' => false, 'closes' => false],
            ['postid' => 11_049, 'background' => 'transparent', 'opens' => false, 'closes' => true],
            ['postid' => 11_238, 'background' => 'neutral', 'opens' => true, 'closes' => true],
            ['postid' => 84, 'background' => 'transparent', 'opens' => true, 'closes' => true],
        ];
        $moduleIds = [53, 51, 11_049, 65, 11_049, 11_229, 11_049, 11_238, 84];
        $cache = [];

        $coldHtml = $this->renderWithFragmentCache($this->renderer($plan), $moduleIds, $cache);
        $warmHtml = $this->renderWithFragmentCache($this->renderer($plan), $moduleIds, $cache);

        static::assertSame($coldHtml, $warmHtml);
        static::assertSame(5, substr_count($warmHtml, 'class="modularity-module-group '));
        static::assertSame(5, substr_count($warmHtml, '</div></div>'));
        static::assertSame(9, substr_count($warmHtml, '<module '));
    }

    /**
     * @param list<array{postid: int, background: string, opens: bool, closes: bool}> $plan
     */
    private function renderer(array $plan): Renderer
    {
        $provider = new LayoutProvider(new GroupNormalizer());
        $cachedPlan = new ReflectionProperty($provider, 'cachedPlan');
        $cachedPlan->setValue($provider, ['content-area' => $plan]);

        return new Renderer($provider);
    }

    /**
     * @param list<int> $moduleIds
     * @param array<string, string> $cache
     */
    private function renderWithFragmentCache(Renderer $renderer, array $moduleIds, array &$cache): string
    {
        $html = '';
        $args = ['id' => 'content-area'];

        foreach ($moduleIds as $moduleId) {
            $module = new WP_Post($moduleId, 'mod-test', 'Module ' . $moduleId);
            $context = $renderer->cacheContext([$module, 'content-area'], $module, $args, []);
            $cacheKey = md5(serialize($context));

            if (!array_key_exists($cacheKey, $cache)) {
                $markup = $renderer->beforeModule('<module id="' . $moduleId . '">', $args, 'mod-test', $moduleId);
                $cache[$cacheKey] = $renderer->afterModule($markup . '</module>', $args, 'mod-test', $moduleId);
            }

            $html .= $cache[$cacheKey];
        }

        return $html;
    }
}
