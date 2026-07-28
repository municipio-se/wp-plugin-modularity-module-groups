<?php

declare(strict_types=1);

namespace MunicipioModularityModuleGroups\Editor;

final class Adapter
{
    private const ASSET_VERSION = '0.1.4';
    private const UNSUPPORTED_SIDEBARS = ['left-sidebar', 'right-sidebar'];

    public function __construct(
        private readonly Compatibility $compatibility,
        private readonly MetaboxRenderer $renderer,
    ) {}

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMetaboxOverrides'], 11);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_notices', [$this, 'renderCompatibilityNotice']);
    }

    public function registerMetaboxOverrides(): void
    {
        if (!$this->compatibility->supportsInstalledVersion()) {
            return;
        }

        $screen = convert_to_screen(get_plugin_page_hookname('modularity-editor', 'options.php'));
        $page = $screen->id;

        add_action(
            'add_meta_boxes_' . $page,
            function () use ($page): void {
                global $wp_meta_boxes;
                global $wp_registered_sidebars;

                foreach ($wp_registered_sidebars as $sidebar) {
                    $sidebarId = (string) ($sidebar['id'] ?? '');
                    if ($sidebarId === '' || in_array($sidebarId, self::UNSUPPORTED_SIDEBARS, true)) {
                        continue;
                    }

                    $id = 'modularity-mb-' . $sidebarId;
                    if (!isset($wp_meta_boxes[$page]['normal']['low'][$id])) {
                        continue;
                    }

                    $wp_meta_boxes[$page]['normal']['low'][$id]['callback'] = [$this->renderer, 'render'];
                }
            },
            11,
        );
    }

    public function enqueueAssets(string $hookSuffix): void
    {
        if ($hookSuffix !== 'admin_page_modularity-editor' || !$this->compatibility->supportsInstalledVersion()) {
            return;
        }

        wp_enqueue_style('modularity-module-groups-editor', $this->assetUrl('css'), [], null);
        wp_enqueue_script(
            'modularity-module-groups-editor',
            $this->assetUrl('js'),
            ['jquery', 'jquery-ui-droppable', 'jquery-ui-sortable'],
            null,
            true,
        );
    }

    private function assetUrl(string $extension): string
    {
        /*
         * Municipio removes every asset query parameter. Keep the version in
         * the filename so editor fixes still invalidate browser caches.
         */
        return MODULARITY_MODULE_GROUPS_URL . 'assets/editor.' . self::ASSET_VERSION . '.' . $extension;
    }

    public function renderCompatibilityNotice(): void
    {
        if ($this->compatibility->supportsInstalledVersion()) {
            return;
        }

        $screen = get_current_screen();
        if ($screen === null || !in_array($screen->id, ['plugins', 'admin_page_modularity-editor'], true)) {
            return;
        }

        $installed = $this->compatibility->installedVersion() ?? __('unknown', 'modularity-module-groups');
        $message = sprintf(
            /* translators: 1: installed Municipio version, 2: supported Municipio version. */
            __(
                'Modularity Module Groups has disabled its group editor because Municipio %1$s is installed. The verified editor adapter supports %2$s. Saved module data has not been changed.',
                'modularity-module-groups',
            ),
            $installed,
            Compatibility::SUPPORTED_MUNICIPIO_VERSION,
        );

        echo '<div class="notice notice-warning"><p>' . esc_html($message) . '</p></div>';
    }
}
