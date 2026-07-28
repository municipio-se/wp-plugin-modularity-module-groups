<?php

declare(strict_types=1);

namespace MunicipioModularityModuleGroups\Tests;

use MunicipioModularityModuleGroups\Background;
use MunicipioModularityModuleGroups\GroupNormalizer;
use PHPUnit\Framework\TestCase;

final class GroupNormalizerTest extends TestCase
{
    private GroupNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new GroupNormalizer();
    }

    public function testItDistinguishesAbsentGroupDataFromTransparentGroupData(): void
    {
        static::assertFalse($this->normalizer->hasGroupData([
            'legacy-default' => ['postid' => 12, 'columnWidth' => 'grid-md-12'],
        ]));
        static::assertTrue($this->normalizer->hasGroupData([
            'explicit-transparent' => ['postid' => 12, 'background' => ''],
        ]));
    }

    public function testItPreservesRowsAndKeysAcrossSaveReloadCycle(): void
    {
        $rows = [
            'row-a' => ['postid' => 12, 'hidden' => false, 'columnWidth' => 'grid-md-6', 'background' => 'secondary'],
            'row-b' => ['postid' => 12, 'hidden' => true, 'columnWidth' => 'grid-md-6', 'background' => 'secondary'],
            'row-c' => ['postid' => 99, 'hidden' => false, 'columnWidth' => '', 'background' => 'card'],
        ];

        $reloaded = $this->normalizer->flatten($this->normalizer->group($rows));

        self::assertSame($rows, $reloaded);
        self::assertSame(['row-a', 'row-b', 'row-c'], array_keys($reloaded));
    }

    public function testItDropsEmptyVisibleGroupsAndMergesNewlyAdjacentGroups(): void
    {
        $rows = [
            'one' => ['postid' => 1, 'background' => 'secondary'],
            'hidden' => ['postid' => 2, 'background' => 'card', 'hidden' => true],
            'missing' => ['postid' => 404, 'background' => 'neutral'],
            'duplicate' => ['postid' => 1, 'background' => 'secondary'],
        ];

        $groups = $this->normalizer->visibleGroups(
            $rows,
            static fn(array $row): bool => !($row['hidden'] ?? false) && $row['postid'] !== 404,
        );

        self::assertCount(1, $groups);
        self::assertSame('secondary', $groups[0]['background']);
        self::assertSame(['one', 'duplicate'], array_keys($groups[0]['modules']));
    }

    public function testItProducesVisibleGroupBoundaries(): void
    {
        $groups = $this->normalizer->group([
            'a' => ['postid' => 10, 'background' => 'mystery'],
            'b' => ['postid' => 11],
            'c' => ['postid' => 12, 'background' => 'white'],
        ]);

        $plan = $this->normalizer->boundaryPlan($groups);

        self::assertSame(
            [
                ['postid' => 10, 'background' => Background::TRANSPARENT, 'opens' => true, 'closes' => false],
                ['postid' => 11, 'background' => Background::TRANSPARENT, 'opens' => false, 'closes' => true],
                ['postid' => 12, 'background' => 'white', 'opens' => true, 'closes' => true],
            ],
            $plan,
        );
    }

    public function testItDoesNotPersistEmptyGroups(): void
    {
        $flattened = $this->normalizer->flatten([
            ['background' => 'card', 'modules' => []],
            ['background' => 'secondary', 'modules' => ['row' => ['postid' => 7]]],
        ]);

        self::assertSame(['row' => ['postid' => 7, 'background' => 'secondary']], $flattened);
    }
}
