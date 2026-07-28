<?php

declare(strict_types=1);

namespace MunicipioModularityModuleGroups\Editor;

use MunicipioModularityModuleGroups\Background;
use MunicipioModularityModuleGroups\GroupNormalizer;

final class MetaboxRenderer
{
    public function __construct(
        private readonly GroupNormalizer $normalizer,
    ) {}

    /**
     * @param mixed $post
     * @param array<string, mixed> $args
     */
    public function render(mixed $post, array $args): void
    {
        $sidebar = (string) ($args['args']['sidebar']['id'] ?? '');
        [$modules, $options] = $this->storedData($post, $sidebar);
        $groups = $this->normalizer->group($modules);

        ?>
        <div class="mmg-editor" data-sidebar-id="<?php echo esc_attr($sidebar); ?>">
            <template data-mmg-group-template>
                <?php $this->renderGroup($sidebar, Background::TRANSPARENT, []); ?>
            </template>

            <ul class="mmg-groups">
                <?php foreach ($groups as $group): ?>
                    <?php $this->renderGroup($sidebar, $group['background'], $group['modules']); ?>
                <?php endforeach; ?>
            </ul>

            <p class="mmg-actions">
                <button type="button" class="button" data-mmg-add-group>
                    <?php esc_html_e('Add group', 'modularity-module-groups'); ?>
                </button>
            </p>
        </div>

        <?php $this->renderSidebarOptions($sidebar, $options); ?>
        <?php
    }

    /**
     * @param array<array-key, array<string, mixed>> $modules
     */
    private function renderGroup(string $sidebar, string $background, array $modules): void
    { ?>
        <li class="mmg-group">
            <div class="mmg-group__header">
                <button
                    type="button"
                    class="mmg-group__handle"
                    aria-label="<?php esc_attr_e('Move group', 'modularity-module-groups'); ?>"
                >
                    <span class="dashicons dashicons-menu" aria-hidden="true"></span>
                </button>
                <label>
                    <span><?php esc_html_e('Background:', 'modularity-module-groups'); ?></span>
                    <select data-mmg-background>
                        <?php foreach (Background::options() as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($background, $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <?php

            /*
             * Municipio's asynchronous module loader targets sidebar areas that
             * already expose data-area-id. The editor adapter restores that
             * attribute after the loader has completed, preventing it from
             * appending every sidebar module to every group.
             */
            ?>
            <ul
                class="mmg-module-list modularity-sidebar-area modularity-js-droppable modularity-js-sortable"
                data-mmg-area-id="<?php echo esc_attr($sidebar); ?>"
                data-empty="<?php esc_attr_e(
                    'Drag modules here. Empty groups are removed automatically.',
                    'modularity-module-groups',
                ); ?>"
            >
                <?php foreach ($modules as $key => $row): ?>
                    <?php $this->renderModule($sidebar, (string) $key, $row, $background); ?>
                <?php endforeach; ?>
            </ul>
        </li>
        <?php }

    /**
     * @param array<string, mixed> $row
     */
    private function renderModule(string $sidebar, string $key, array $row, string $background): void
    {
        $postId = (int) ($row['postid'] ?? 0);
        $post = $postId > 0 ? get_post($postId) : null;
        $postType = $post instanceof \WP_Post ? $post->post_type : '';
        $available = \Modularity\ModuleManager::$available ?? [];
        $postTypeLabel = $postType !== '' ? $available[$postType]['labels']['name'] ?? $postType : '';
        $postTitle = $post instanceof \WP_Post ? $post->post_title : '';
        $columnWidth = is_string($row['columnWidth'] ?? null) ? $row['columnWidth'] : '';
        $hidden = in_array($row['hidden'] ?? false, [true, 1, '1', 'true', 'hidden'], true);
        $rowName = 'modularity_modules[' . $sidebar . '][' . $key . ']';
        $editUrl = $postId > 0
            ? admin_url(
                'post.php?'
                    . http_build_query([
                        'post' => $postId,
                        'action' => 'edit',
                        'is_thickbox' => 'true',
                    ]),
            )
            : admin_url(
                'post-new.php?'
                    . http_build_query([
                        'post_type' => $postType,
                        'is_thickbox' => 'true',
                    ]),
            );
        $importUrl = admin_url(
            'edit.php?'
                . http_build_query([
                    'post_type' => $postType,
                    'is_thickbox' => 'true',
                ]),
        );

        ?>
        <li
            id="post-<?php echo esc_attr((string) $postId); ?>"
            data-module-id="<?php echo esc_attr($postType); ?>"
            data-module-stored-width="<?php echo esc_attr($columnWidth); ?>"
        >
            <span class="modularity-line-wrapper">
                <span class="modularity-sortable-handle">
                    <i
                        style="top:4px;"
                        class="modularity-module-actions-symbol material-symbols material-symbols-rounded material-symbols-sharp material-symbols-outlined"
                        aria-hidden="true"
                    >drag_handle</i>
                </span>
                <span class="modularity-module-name">
                    <?php if ($postTypeLabel !== ''): ?>
                        <strong><?php echo esc_html($postTypeLabel); ?></strong>
                    <?php endif; ?>
                    <span class="modularity-module-title">
                        <?php echo
                            esc_html(
                                $postTitle !== ''
                                    ? ': ' . $postTitle
                                    : __('(Deactivated module)', 'modularity-module-groups'),
                            )
                        ; ?>
                    </span>
                </span>
                <span class="modularity-module-actions">
                    <label class="modularity-module-columns">
                        <i
                            style="top:4px;"
                            class="modularity-cmd-visibility-on modularity-module-actions-symbol material-symbols material-symbols-rounded material-symbols-sharp material-symbols-outlined"
                            aria-hidden="true"
                        >width</i>
                        <select name="<?php echo esc_attr($rowName . '[columnWidth]'); ?>">
                            <?php foreach ($this->widthOptions() as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected(
                                    $columnWidth,
                                    $value,
                                ); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="label"><?php esc_html_e('Width', 'modularity'); ?></span>
                    </label>
                    <label class="modularity-module-hide">
                        <input
                            type="checkbox"
                            name="<?php echo esc_attr($rowName . '[hidden]'); ?>"
                            value="hidden"
                            <?php checked($hidden); ?>
                            aria-label="<?php esc_attr_e('Visibility', 'modularity'); ?>"
                        >
                        <i
                            style="top:4px;"
                            class="modularity-cmd-visibility-on modularity-module-actions-symbol material-symbols material-symbols-rounded material-symbols-sharp material-symbols-outlined"
                            aria-hidden="true"
                        >visibility</i>
                        <i
                            style="top:4px;"
                            class="modularity-cmd-visibility-off modularity-module-actions-symbol material-symbols material-symbols-rounded material-symbols-sharp material-symbols-outlined"
                            aria-hidden="true"
                        >visibility_off</i>
                        <span class="label"><?php esc_html_e('Visibility', 'modularity'); ?></span>
                    </label>
                    <a href="<?php echo
                        esc_url($editUrl)
                    ; ?>" data-modularity-modal class="modularity-js-thickbox-open modularity-err-resolver">
                        <i
                            style="top:3px;"
                            class="modularity-module-actions-symbol material-symbols material-symbols-rounded material-symbols-sharp material-symbols-outlined"
                            aria-hidden="true"
                        >edit</i>
                        <span class="label"><?php esc_html_e('Edit', 'modularity'); ?></span>
                    </a>
                    <a href="<?php echo
                        esc_url($importUrl)
                    ; ?>" class="modularity-js-thickbox-import modularity-err-resolver">
                        <i
                            style="top:4px;"
                            class="modularity-module-actions-symbol material-symbols material-symbols-rounded material-symbols-sharp material-symbols-outlined"
                            aria-hidden="true"
                        >dataset_linked</i>
                        <span class="label"><?php esc_html_e('Import', 'modularity'); ?></span>
                    </a>
                    <a href="#remove" class="modularity-module-remove modularity-err-resolver">
                        <i
                            style="top:4px;"
                            class="modularity-module-actions-symbol material-symbols material-symbols-rounded material-symbols-sharp material-symbols-outlined"
                            aria-hidden="true"
                        >delete</i>
                        <span class="label"><?php esc_html_e('Remove', 'modularity'); ?></span>
                    </a>
                </span>
                <input
                    type="hidden"
                    name="<?php echo esc_attr($rowName . '[postid]'); ?>"
                    class="modularity-js-module-id"
                    value="<?php echo esc_attr((string) $postId); ?>"
                    required
                >
                <input type="hidden" name="<?php echo esc_attr($rowName . '[name]'); ?>" value="<?php echo
                    esc_attr($postType)
                ; ?>">
                <input
                    type="hidden"
                    name="<?php echo esc_attr($rowName . '[background]'); ?>"
                    value="<?php echo esc_attr($this->storageBackground($background)); ?>"
                    data-mmg-module-background
                >
            </span>
        </li>
        <?php
    }

    /**
     * @return array<string, string>
     */
    private function widthOptions(): array
    {
        return [
            '' => __('Inherit', 'modularity'),
            'grid-md-12' => '100%',
            'grid-md-9' => '75%',
            'grid-md-8' => '66%',
            'grid-md-6' => '50%',
            'grid-md-4' => '33%',
            'grid-md-3' => '25%',
        ];
    }

    private function storageBackground(string $background): string
    {
        return $background === Background::TRANSPARENT ? '' : $background;
    }

    /**
     * @return array{array<array-key, array<string, mixed>>, array<string, mixed>}
     */
    private function storedData(mixed $post, string $sidebar): array
    {
        if (\Modularity\Helper\Post::isArchive()) {
            global $archive;
            $options = get_option('modularity_' . $archive . '_sidebar-options');
            $modules = get_option('modularity_' . $archive . '_modules');
        } else {
            $postId = $post instanceof \WP_Post ? $post->ID : (int) ($_GET['id'] ?? 0);
            $options = get_post_meta($postId, 'modularity-sidebar-options', true);
            $modules = get_post_meta($postId, 'modularity-modules', true);
        }

        $sidebarModules = is_array($modules) && is_array($modules[$sidebar] ?? null) ? $modules[$sidebar] : [];
        $sidebarOptions = is_array($options) && is_array($options[$sidebar] ?? null) ? $options[$sidebar] : [];

        return [$sidebarModules, $sidebarOptions];
    }

    /**
     * @param array<string, mixed> $options
     */
    private function renderSidebarOptions(string $sidebar, array $options): void
    { ?>
        <div class="modularity-sidebar-options">
            <div class="container">
                <div class="col">
                    <?php esc_html_e('Show modules', 'modularity'); ?>
                    <select name="modularity_sidebar_options[<?php echo esc_attr($sidebar); ?>][hook]">
                        <option value="before" <?php selected('before', $options['hook'] ?? ''); ?>>
                            <?php esc_html_e('before', 'modularity'); ?>
                        </option>
                        <option value="after" <?php selected('after', $options['hook'] ?? ''); ?>>
                            <?php esc_html_e('after', 'modularity'); ?>
                        </option>
                    </select>
                    <?php esc_html_e('widgets', 'modularity-module-groups'); ?>
                </div>
                <div class="col">
                    <label>
                        <input
                            type="checkbox"
                            value="true"
                            name="modularity_sidebar_options[<?php echo esc_attr($sidebar); ?>][hide_widgets]"
                            <?php checked(isset($options['hide_widgets'])); ?>
                        >
                        <?php esc_html_e('Hide global widgets', 'modularity'); ?>
                    </label>
                </div>
            </div>
        </div>
        <?php }
}
