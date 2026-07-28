<?php

declare(strict_types=1);

namespace MunicipioModularityModuleGroups;

final class GroupNormalizer
{
    /**
     * A layout without the LTS field must remain a frontend no-op. Once any
     * row carries the field, missing values inside that layout still normalize
     * to transparent and participate in the established grouping contract.
     *
     * @param array<array-key, array<string, mixed>> $rows
     */
    public function hasGroupData(array $rows): bool
    {
        foreach ($rows as $row) {
            if (array_key_exists('background', $row)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Group identity is deliberately implicit: adjacent rows with the same
     * normalized background form one group, matching the established LTS data
     * contract without introducing IDs or a migration.
     *
     * @param array<array-key, array<string, mixed>> $rows
     * @return list<array{background: string, modules: array<array-key, array<string, mixed>>}>
     */
    public function group(array $rows): array
    {
        $groups = [];

        foreach ($rows as $key => $row) {
            $background = Background::normalize($row['background'] ?? null);
            $lastIndex = count($groups) - 1;

            if ($lastIndex < 0 || $groups[$lastIndex]['background'] !== $background) {
                $groups[] = [
                    'background' => $background,
                    'modules' => [],
                ];
                $lastIndex++;
            }

            $groups[$lastIndex]['modules'][$key] = $row;
        }

        return $groups;
    }

    /**
     * Empty visible groups are removed and newly adjacent groups with the same
     * background are merged. This avoids emitting empty frontend wrappers when
     * a stored module is hidden, missing, or unavailable.
     *
     * @param array<array-key, array<string, mixed>> $rows
     * @param callable(array<string, mixed>): bool $isVisible
     * @return list<array{background: string, modules: array<array-key, array<string, mixed>>}>
     */
    public function visibleGroups(array $rows, callable $isVisible): array
    {
        $visibleGroups = [];

        foreach ($this->group($rows) as $group) {
            $modules = array_filter($group['modules'], $isVisible);

            if ($modules === []) {
                continue;
            }

            $lastIndex = count($visibleGroups) - 1;
            if ($lastIndex >= 0 && $visibleGroups[$lastIndex]['background'] === $group['background']) {
                $visibleGroups[$lastIndex]['modules'] += $modules;
                continue;
            }

            $visibleGroups[] = [
                'background' => $group['background'],
                'modules' => $modules,
            ];
        }

        return $visibleGroups;
    }

    /**
     * @param list<array{background: string, modules: array<array-key, array<string, mixed>>}> $groups
     * @return array<array-key, array<string, mixed>>
     */
    public function flatten(array $groups): array
    {
        $rows = [];

        foreach ($groups as $group) {
            foreach ($group['modules'] as $key => $row) {
                $row['background'] = $group['background'] === Background::TRANSPARENT ? '' : $group['background'];
                $rows[$key] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param list<array{background: string, modules: array<array-key, array<string, mixed>>}> $groups
     * @return list<array{postid: int, background: string, opens: bool, closes: bool}>
     */
    public function boundaryPlan(array $groups): array
    {
        $plan = [];

        foreach ($groups as $group) {
            $modules = array_values($group['modules']);
            $lastIndex = count($modules) - 1;

            foreach ($modules as $index => $row) {
                $plan[] = [
                    'postid' => (int) ($row['postid'] ?? 0),
                    'background' => $group['background'],
                    'opens' => $index === 0,
                    'closes' => $index === $lastIndex,
                ];
            }
        }

        return $plan;
    }
}
