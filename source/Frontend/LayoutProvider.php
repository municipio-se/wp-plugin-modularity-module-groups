<?php

declare(strict_types=1);

namespace MunicipioModularityModuleGroups\Frontend;

use MunicipioModularityModuleGroups\GroupNormalizer;

final class LayoutProvider
{
    private const UNGROUPED_SIDEBARS = ['left-sidebar', 'right-sidebar'];

    /** @var array<string, list<array{postid: int, background: string, opens: bool, closes: bool}>>|null */
    private ?array $cachedPlan = null;

    public function __construct(
        private readonly GroupNormalizer $normalizer,
    ) {}

    /**
     * @return array<string, list<array{postid: int, background: string, opens: bool, closes: bool}>>
     */
    public function plan(): array
    {
        if ($this->cachedPlan !== null) {
            return $this->cachedPlan;
        }

        $layout = $this->currentStoredLayout();
        $plan = [];
        $contentHasSidebars = is_active_sidebar('left-sidebar') || is_active_sidebar('right-sidebar');

        foreach ($layout as $sidebar => $rows) {
            if (!is_array($rows)) {
                continue;
            }

            if (
                in_array((string) $sidebar, self::UNGROUPED_SIDEBARS, true)
                || $sidebar === 'content-area' && $contentHasSidebars
            ) {
                continue;
            }

            $groups = $this->normalizer->visibleGroups($rows, fn(array $row): bool => $this->isRenderable($row));
            $plan[(string) $sidebar] = $this->normalizer->boundaryPlan($groups);
        }

        return $this->cachedPlan = $plan;
    }

    /**
     * Reading the existing LTS-compatible structure is intentionally side
     * effect free. The plugin has no activation hook or write migration.
     *
     * @return array<string, array<array-key, array<string, mixed>>>
     */
    private function currentStoredLayout(): array
    {
        global $post;
        global $wp_query;

        if (isset($wp_query->query['modularity_template']) && $wp_query->query['modularity_template'] !== '') {
            $layout = get_option('modularity_' . $wp_query->query['modularity_template'] . '_modules');
        } else {
            $archiveSlug = \Modularity\Helper\Wp::getArchiveSlug();
            if ($archiveSlug) {
                $layout = get_option('modularity_' . $archiveSlug . '_modules');
            } elseif ($post instanceof \WP_Post) {
                $postId = \Modularity\Editor::pageForPostTypeTranscribe($post->ID);
                $layout = is_numeric($postId)
                    ? get_post_meta((int) $postId, 'modularity-modules', true)
                    : get_option('modularity_' . $postId . '_modules');
            } else {
                $layout = [];
            }
        }

        return is_array($layout) ? $layout : [];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function isRenderable(array $row): bool
    {
        $postId = (int) ($row['postid'] ?? 0);
        if ($postId <= 0) {
            return false;
        }

        $module = get_post($postId);
        if (!$module instanceof \WP_Post) {
            return false;
        }

        $available = \Modularity\ModuleManager::$available ?? [];
        if (!isset($available[$module->post_type])) {
            return false;
        }

        $allowedStatuses = is_user_logged_in() ? ['publish', 'private'] : ['publish'];
        if (!in_array($module->post_status, $allowedStatuses, true)) {
            return false;
        }

        if (is_preview()) {
            return true;
        }

        return !in_array($row['hidden'] ?? false, [true, 1, '1', 'true', 'hidden'], true);
    }
}
