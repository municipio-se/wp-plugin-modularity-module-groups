<?php

declare(strict_types=1);

namespace MunicipioModularityModuleGroups\Editor;

use Composer\InstalledVersions;

final class Compatibility
{
    public const SUPPORTED_MUNICIPIO_VERSION = '6.43.2';

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

        $normalized = ltrim($version, 'v');

        return version_compare($normalized, self::SUPPORTED_MUNICIPIO_VERSION, '==');
    }
}
