<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Utility;

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Package\PackageManager;

class ExtensionUtility
{
    private static ?PackageManager $packageManager = null;

    /**
     * Only use this in a Services.php. Get the PackageManager from the DI container / makeInstance otherwise.
     *
     * @param string $extension
     * @return bool
     */
    public static function isLoaded(string $extension): bool
    {
        return self::createPackageManager()->isPackageActive($extension);
    }

    private static function createPackageManager(): PackageManager
    {
        if (null === self::$packageManager) {
            $coreCache = Bootstrap::createCache('core');
            $packageCache = Bootstrap::createPackageCache($coreCache);
            self::$packageManager = Bootstrap::createPackageManager(PackageManager::class, $packageCache);
        }
        return self::$packageManager;
    }
}
