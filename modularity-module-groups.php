<?php

/**
 * Plugin Name: Modularity Module Groups
 * Description: Adds module-group editing and full-width grouped rendering to modern Municipio.
 * Version: 0.1.0
 * Requires PHP: 8.2
 * Author: Whitespace
 * License: MIT
 * Text Domain: modularity-module-groups
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit();
}

define('MODULARITY_MODULE_GROUPS_PATH', plugin_dir_path(__FILE__));
define('MODULARITY_MODULE_GROUPS_URL', plugin_dir_url(__FILE__));

$autoload = MODULARITY_MODULE_GROUPS_PATH . 'vendor/autoload.php';

if (is_readable($autoload)) {
    require_once $autoload;
}

if (class_exists(\MunicipioModularityModuleGroups\Plugin::class)) {
    (new \MunicipioModularityModuleGroups\Plugin())->register();
}
