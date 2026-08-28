<?php

declare(strict_types=1);

namespace MunicipioModularityModuleGroups\Tests;

use MunicipioModularityModuleGroups\Editor\Compatibility;
use PHPUnit\Framework\TestCase;

final class CompatibilityTest extends TestCase
{
    public function testItAcceptsTheVerifiedMunicipioPatchRange(): void
    {
        self::assertTrue(Compatibility::supportsVersion('6.43.2'));
        self::assertTrue(Compatibility::supportsVersion('v6.43.2'));
        self::assertTrue(Compatibility::supportsVersion('6.43.3'));
        self::assertTrue(Compatibility::supportsVersion('6.43.99'));
        self::assertFalse(Compatibility::supportsVersion('6.43.1'));
        self::assertFalse(Compatibility::supportsVersion('6.44.0'));
        self::assertFalse(Compatibility::supportsVersion('7.0.0'));
        self::assertFalse(Compatibility::supportsVersion('dev-main'));
        self::assertFalse(Compatibility::supportsVersion(null));
    }
}
