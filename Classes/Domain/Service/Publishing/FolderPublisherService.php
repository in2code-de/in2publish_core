<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Service\Publishing;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use In2code\In2publishCore\Domain\Driver\RemoteFileAbstractionLayerDriver;
use In2code\In2publishCore\Event\FolderWasPublished;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function basename;
use function dirname;
use function trim;

class FolderPublisherService
{
    protected EventDispatcher $eventDispatcher;

    protected ResourceFactory $resourceFactory;

    private RemoteFileAbstractionLayerDriver $remoteFalDriver;

    public function __construct(
        EventDispatcher $eventDispatcher,
        ResourceFactory $resourceFactory,
        RemoteFileAbstractionLayerDriver $remoteFalDriver
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->resourceFactory = $resourceFactory;
        $this->remoteFalDriver = $remoteFalDriver;
    }

    public function publish(string $combinedIdentifier): bool
    {
        [$storage, $folderIdentifier] = GeneralUtility::trimExplode(':', $combinedIdentifier);
        $storage = (int)$storage;

        $this->remoteFalDriver->setStorageUid($storage);
        $this->remoteFalDriver->initialize();

        // Determine if the folder should get published or deleted.
        // If it exists locally then create it on foreign else remove it.
        if ($this->resourceFactory->getStorageObject($storage)->hasFolder($folderIdentifier)) {
            $createdFolder = $this->remoteFalDriver->createFolder(
                basename($folderIdentifier),
                dirname($folderIdentifier),
                true
            );
            // Ignore slashes because of drivers which do not return the
            // leading or trailing slashes (like fal_s3/aus_driver_amazon_s3)
            $success = trim($folderIdentifier, '/') === trim($createdFolder, '/');
        } else {
            $success = $this->remoteFalDriver->deleteFolder($folderIdentifier, true);
        }
        $this->eventDispatcher->dispatch(new FolderWasPublished($storage, $folderIdentifier, $success));
        return $success;
    }
}
