<?php

declare(strict_types=1);

namespace MunicipioModularityModuleGroups\Frontend;

use MunicipioModularityModuleGroups\Background;

final class Renderer
{
    /** @var array<string, int> */
    private array $indexes = [];

    /** @var array<string, array{postid: int, background: string, opens: bool, closes: bool}> */
    private array $current = [];

    public function __construct(
        private readonly LayoutProvider $layoutProvider,
    ) {}

    public function register(): void
    {
        add_filter('Modularity/Display/BeforeModule', [$this, 'beforeModule'], 20, 4);
        add_filter('Modularity/Display/AfterModule', [$this, 'afterModule'], 20, 4);
        add_action('wp_enqueue_scripts', [$this, 'enqueueStyles']);
    }

    /**
     * @param array<string, mixed> $args
     */
    public function beforeModule(string $markup, array $args, string $postType, int $postId): string
    {
        $sidebar = (string) ($args['id'] ?? '');
        $placement = $this->nextPlacement($sidebar, $postId);

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

    public function enqueueStyles(): void
    {
        wp_enqueue_style('modularity-module-groups', MODULARITY_MODULE_GROUPS_URL . 'assets/frontend.css', [], '0.1.0');
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
