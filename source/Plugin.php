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
        $normalizer = new GroupNormalizer();

        (new Adapter(new Compatibility(), new MetaboxRenderer($normalizer)))->register();
        (new Renderer(new LayoutProvider($normalizer)))->register();
    }
}
