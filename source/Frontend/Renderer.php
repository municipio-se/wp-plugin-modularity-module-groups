<?php

declare(strict_types=1);

namespace MunicipioModularityModuleGroups\Frontend;

use MunicipioModularityModuleGroups\Background;

final class Renderer
{
    private const ASSET_VERSION = '0.1.5';

    /** @var array<string, int> */
    private array $indexes = [];

    /** @var array<string, array{postid: int, background: string, opens: bool, closes: bool}> */
    private array $current = [];

    public function __construct(
        private readonly LayoutProvider $layoutProvider,
    ) {}

    public function register(): void
    {
        add_filter('Modularity/Display/CacheContext', [$this, 'cacheContext'], 10, 4);
        add_filter('Modularity/Display/BeforeModule', [$this, 'beforeModule'], 20, 4);
        add_filter('Modularity/Display/AfterModule', [$this, 'afterModule'], 20, 4);
        add_action('wp', [$this, 'disableFragmentCacheWithoutContextFilter'], 20);
        add_action('wp_enqueue_scripts', [$this, 'enqueueStyles']);
    }

    /**
     * Module group wrappers belong to a placed module instance, not the
     * reusable module post. Include the resolved placement in Modularity's
     * fragment-cache context so repeated modules cannot replay another row's
     * opening or closing tags.
     *
     * @param array<string, mixed> $args
     * @param array<string, mixed> $moduleSettings
     */
    public function cacheContext(mixed $context, \WP_Post $module, array $args, array $moduleSettings): mixed
    {
        $sidebar = (string) ($args['id'] ?? '');
        $placement = $this->nextPlacement($sidebar, $module->ID);

        if ($placement === null) {
            return $context;
        }

        $this->current[$sidebar] = $placement;

        return [
            $context,
            'modularity-module-group' => $placement,
        ];
    }

    /**
     * @param array<string, mixed> $args
     */
    public function beforeModule(string $markup, array $args, string $postType, int $postId): string
    {
        $sidebar = (string) ($args['id'] ?? '');
        $placement = $this->current[$sidebar] ?? $this->nextPlacement($sidebar, $postId);

        if ($placement === null) {
            return $markup;
        }

        $this->current[$sidebar] = $placement;

        if (!$placement['opens']) {
            return $markup;
        }

        $classes = array_merge(['modularity-module-group', 'o-grid-12'], Background::classes($placement['background']));
        $opening =
            '<div class="'
            . esc_attr(implode(' ', $classes))
            . '" data-module-group-background="'
            . esc_attr($placement['background'])
            . '"><div class="modularity-module-group__grid o-grid">';

        return $opening . $markup;
    }

    /**
     * @param array<string, mixed> $args
     */
    public function afterModule(string $markup, array $args, string $postType, int $postId): string
    {
        $sidebar = (string) ($args['id'] ?? '');
        $placement = $this->current[$sidebar] ?? null;
        unset($this->current[$sidebar]);

        if ($placement === null || !$placement['closes']) {
            return $markup;
        }

        return $markup . '</div></div>';
    }

    /**
     * Older Municipio releases cache filtered module markup without an
     * extension context. Disable that cache only on pages with grouped module
     * layouts until the cache-context contract is available. Correct wrapper
     * nesting takes precedence over fragment caching.
     */
    public function disableFragmentCacheWithoutContextFilter(): void
    {
        if (
            defined(\Modularity\Display::class . '::CACHE_CONTEXT_FILTER_VERSION')
            || $this->layoutProvider->plan() === []
            || defined('MODULARITY_DISABLE_FRAGMENT_CACHE')
        ) {
            return;
        }

        define('MODULARITY_DISABLE_FRAGMENT_CACHE', true);
    }

    public function enqueueStyles(): void
    {
        /*
         * Municipio removes every asset query parameter. Keep the version in
         * the filename so frontend fixes still invalidate browser caches.
         */
        wp_enqueue_style(
            'modularity-module-groups',
            MODULARITY_MODULE_GROUPS_URL . 'assets/frontend.' . self::ASSET_VERSION . '.css',
            [],
            null,
        );
    }

    /**
     * @return array{postid: int, background: string, opens: bool, closes: bool}|null
     */
    private function nextPlacement(string $sidebar, int $postId): ?array
    {
        $plan = $this->layoutProvider->plan()[$sidebar] ?? [];
        $index = $this->indexes[$sidebar] ?? 0;

        while (isset($plan[$index]) && $plan[$index]['postid'] !== $postId) {
            $index++;
        }

        if (!isset($plan[$index])) {
            return null;
        }

        $this->indexes[$sidebar] = $index + 1;

        return $plan[$index];
    }
}
