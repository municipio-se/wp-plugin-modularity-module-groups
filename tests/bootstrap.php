<?php

declare(strict_types=1);

function __(string $text, string $domain = 'default'): string
{
    return $text;
}

function esc_attr(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function esc_html(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function esc_html_e(string $text, string $domain = 'default'): void
{
    echo esc_html($text);
}

function esc_attr_e(string $text, string $domain = 'default'): void
{
    echo esc_attr($text);
}

function esc_url(string $url): string
{
    return esc_attr($url);
}

function admin_url(string $path): string
{
    return 'https://example.test/wp-admin/' . ltrim($path, '/');
}

function wp_json_encode(mixed $value): string|false
{
    return json_encode($value);
}

function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
{
    global $testFilters;

    return isset($testFilters[$hook]) ? $testFilters[$hook]($value, ...$args) : $value;
}

function get_post(int $postId): ?WP_Post
{
    global $testPosts;

    return $testPosts[$postId] ?? null;
}

function selected(mixed $selected, mixed $current, bool $display = true): string
{
    $result = $selected === $current ? 'selected="selected"' : '';
    if ($display) {
        echo $result;
    }

    return $result;
}

function checked(mixed $checked, mixed $current = true, bool $display = true): string
{
    $result = $checked === $current ? 'checked="checked"' : '';
    if ($display) {
        echo $result;
    }

    return $result;
}

class WP_Post
{
    public function __construct(
        public int $ID,
        public string $post_type,
        public string $post_title,
    ) {}
}

class_alias(TestModuleManager::class, 'Modularity\\ModuleManager');

final class TestModuleManager
{
    public static array $available = [];
}

require dirname(__DIR__) . '/vendor/autoload.php';
