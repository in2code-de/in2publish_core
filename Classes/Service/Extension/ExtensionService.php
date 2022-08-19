<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Extension;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class ExtensionService
{
    /**
     * Advantage: Extension version is not cached.
     *
     * @see ExtensionManagementUtility::getExtensionVersion()
     *
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function getExtensionVersion(string $extension): string
    {
        $EM_CONF = [];
        $_EXTKEY = $extension;
        require(ExtensionManagementUtility::extPath($extension, 'ext_emconf.php'));
        /** @psalm-suppress EmptyArrayAccess */
        return $EM_CONF[$_EXTKEY]['version'];
    }
}
