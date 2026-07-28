<?php

declare(strict_types=1);

namespace MunicipioModularityModuleGroups;

use MunicipioModularityModuleGroups\Editor\Adapter;
use MunicipioModularityModuleGroups\Editor\Compatibility;
use MunicipioModularityModuleGroups\Editor\MetaboxRenderer;
use MunicipioModularityModuleGroups\Frontend\LayoutProvider;
use MunicipioModularityModuleGroups\Frontend\Renderer;

final class Plugin
{
    public function register(): void
    {
        add_action('init', [$this, 'loadTranslations']);

        $normalizer = new GroupNormalizer();

        (new Adapter(new Compatibility(), new MetaboxRenderer($normalizer)))->register();
        (new Renderer(new LayoutProvider($normalizer)))->register();
    }

    public function loadTranslations(): void
    {
        load_plugin_textdomain(
            'modularity-module-groups',
            false,
            dirname(plugin_basename(MODULARITY_MODULE_GROUPS_FILE)) . '/languages',
        );
    }
}
