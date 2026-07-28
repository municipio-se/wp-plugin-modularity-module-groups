<?php

declare(strict_types=1);

namespace MunicipioModularityModuleGroups;

final class Background
{
    public const TRANSPARENT = 'transparent';

    /**
     * The persisted values are inherited from Municipio LTS. An empty value is
     * the historical representation of a transparent group.
     *
     * @var list<string>
     */
    private const SUPPORTED = [
        self::TRANSPARENT,
        'complementary',
        'secondary',
        'white',
        'neutral',
        'card',
        'background',
    ];

    public static function normalize(mixed $value): string
    {
        if ($value === '' || $value === null) {
            return self::TRANSPARENT;
        }

        if (!is_string($value) || !in_array($value, self::SUPPORTED, true)) {
            return self::TRANSPARENT;
        }

        return $value;
    }

    /**
     * @return list<string>
     */
    public static function classes(mixed $value): array
    {
        return match (self::normalize($value)) {
            'complementary' => ['modularity-module-group--complementary', 'layer-white'],
            'secondary' => ['modularity-module-group--secondary', 'layer-white'],
            'white' => ['modularity-module-group--white'],
            'neutral' => ['modularity-module-group--neutral', 'layer-white'],
            'card' => ['modularity-module-group--card', 'layer-background'],
            'background' => ['modularity-module-group--background', 'layer-background'],
            default => ['modularity-module-group--transparent'],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::TRANSPARENT => __('No background', 'modularity-module-groups'),
            'complementary' => __('Complementary color', 'modularity-module-groups'),
            'secondary' => __('Secondary color', 'modularity-module-groups'),
            'white' => __('White', 'modularity-module-groups'),
            'neutral' => __('Neutral color', 'modularity-module-groups'),
            'card' => __('Card color', 'modularity-module-groups'),
            'background' => __('Background color', 'modularity-module-groups'),
        ];
    }
}
