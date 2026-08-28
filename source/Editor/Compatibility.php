<?php

declare(strict_types=1);

namespace MunicipioModularityModuleGroups\Editor;

use Composer\InstalledVersions;

final class Compatibility
{
    public const MINIMUM_MUNICIPIO_VERSION = '6.43.2';
    public const MAXIMUM_MUNICIPIO_VERSION = '6.44.0';
    public const SUPPORTED_MUNICIPIO_VERSIONS = '>=6.43.2 <6.44.0';

    public function installedVersion(): ?string
    {
        if (!class_exists(InstalledVersions::class)) {
            return null;
        }

        try {
            return InstalledVersions::getPrettyVersion('helsingborg-stad/municipio');
        } catch (\OutOfBoundsException) {
            return null;
        }
    }

    public function supportsInstalledVersion(): bool
    {
        return self::supportsVersion($this->installedVersion());
    }

    public static function supportsVersion(?string $version): bool
    {
        if ($version === null) {
            return false;
        }

        $normalized = preg_replace('/^v/', '', $version);
        if ($normalized === null || preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $normalized) !== 1) {
            return false;
        }

        return (
            version_compare($normalized, self::MINIMUM_MUNICIPIO_VERSION, '>=')
            && version_compare($normalized, self::MAXIMUM_MUNICIPIO_VERSION, '<')
        );
    }
}
