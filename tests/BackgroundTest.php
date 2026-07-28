<?php

declare(strict_types=1);

namespace MunicipioModularityModuleGroups\Tests;

use MunicipioModularityModuleGroups\Background;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BackgroundTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function normalizationCases(): iterable
    {
        yield 'missing' => [null, Background::TRANSPARENT];
        yield 'legacy empty' => ['', Background::TRANSPARENT];
        yield 'unknown' => ['brand-special', Background::TRANSPARENT];
        yield 'broken array' => [['secondary'], Background::TRANSPARENT];
        yield 'known' => ['secondary', 'secondary'];
    }

    #[DataProvider('normalizationCases')]
    public function testItNormalizesBackgroundsSafely(mixed $value, string $expected): void
    {
        self::assertSame($expected, Background::normalize($value));
    }

    public function testItMapsEveryContractValueToCurrentClasses(): void
    {
        foreach (array_keys(Background::options()) as $background) {
            self::assertNotEmpty(Background::classes($background));
        }

        self::assertSame(['modularity-module-group--transparent'], Background::classes('unknown'));
    }
}
