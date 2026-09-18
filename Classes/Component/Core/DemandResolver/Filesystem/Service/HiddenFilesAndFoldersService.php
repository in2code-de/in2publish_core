<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\Filter\FileNameFilter;

use function str_contains;

class HiddenFilesAndFoldersService
{
    protected ?bool $showHiddenFilesAndFolders = null;

    public function showHiddenFilesAndFolders(): bool
    {
        if (null === $this->showHiddenFilesAndFolders) {
            $backendUser = $GLOBALS['BE_USER'] ?? null;
            if ($backendUser instanceof BackendUserAuthentication) {
                $backendUser->evaluateUserSpecificFileFilterSettings();
            }
            $this->showHiddenFilesAndFolders = FileNameFilter::getShowHiddenFilesAndFolders();
        }
        return $this->showHiddenFilesAndFolders;
    }

    /**
     * @see FileNameFilter::filterHiddenFilesAndFolders()
     */
    public function isHidden(string $identifier): bool
    {
        return str_contains($identifier, '/.');
    }

    public function shouldBeSkipped(string $identifier): bool
    {
        return $this->isHidden($identifier) && $this->showHiddenFilesAndFolders() === false;
    }
}
