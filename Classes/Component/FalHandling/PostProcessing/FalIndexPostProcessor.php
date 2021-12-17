<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\FalHandling\PostProcessing;

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

use In2code\In2publishCore\Component\FalHandling\Finder\IndexingFalFinder;
use In2code\In2publishCore\Domain\Driver\RemoteStorage;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Utility\FileUtility;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;

class FalIndexPostProcessor implements PostProcessor, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected ResourceFactory $resourceFactory;

    protected RemoteStorage $remoteStorage;

    protected IndexingFalFinder $indexingFalFinder;

    public function __construct(
        ResourceFactory $resourceFactory,
        RemoteStorage $remoteStorage,
        IndexingFalFinder $indexingFalFinder
    ) {
        $this->resourceFactory = $resourceFactory;
        $this->remoteStorage = $remoteStorage;
        $this->indexingFalFinder = $indexingFalFinder;
    }

    /** @param RecordInterface[] $records */
    public function postProcess(array $records): void
    {
        $identifiers = [];
        foreach ($records as $record) {
            $storage = $this->getStorage($record);
            $identifier = $record->getForeignProperty('identifier') ?? $record->getLocalProperty('identifier');
            $identifiers[$storage->getUid()][] = $identifier;
        }
        $this->remoteStorage->prefetch($identifiers);

        foreach ($records as $file) {
            $storage = $this->getStorage($file);

            if (null !== $storage) {
                $localIdentifier = '';
                if ($file->hasLocalProperty('identifier')) {
                    $localIdentifier = $file->getLocalProperty('identifier');
                }
                $foreignIdentifier = '';
                if ($file->hasForeignProperty('identifier')) {
                    $foreignIdentifier = $file->getForeignProperty('identifier');
                }

                if ($file->getState() !== RecordInterface::RECORD_STATE_MOVED) {
                    $localIdentifier = $localIdentifier !== '' ? $localIdentifier : $foreignIdentifier;
                    $foreignIdentifier = $foreignIdentifier !== '' ? $foreignIdentifier : $localIdentifier;
                }

                if ($storage->hasFile($localIdentifier)) {
                    $localFile = $storage->getFile($localIdentifier);
                    $localFileInfo = FileUtility::extractFileInformation($localFile);
                } else {
                    $localFileInfo = [];
                }

                $foreignFileInfo = $this->remoteStorage->getFile($storage->getUid(), $foreignIdentifier);

                $this->indexingFalFinder->overruleLocalStorage($storage);
                $this->indexingFalFinder->overruleRemoteStorage($this->remoteStorage);
                // do not use the return value since we only desire the record update of the file
                $this->indexingFalFinder->filterRecords(
                    [$localIdentifier => $localFileInfo],
                    [$foreignIdentifier => $foreignFileInfo],
                    [$file]
                );
            }
        }
    }

    protected function getStorage(RecordInterface $record): ?ResourceStorage
    {
        static $storages = [];
        if (!isset($storages[0])) {
            $storages[0] = $this->resourceFactory->getStorageObject(0);
        }

        if (null !== ($storageUid = $record->getLocalProperty('storage'))) {
            try {
                $storages[$storageUid] = $this->resourceFactory->getStorageObject($storageUid);
            } catch (InvalidArgumentException $exception) {
                $this->logger->warning(
                    'Storage or driver for record does not exist',
                    [
                        'exception' => $exception,
                        'storage' => $record->getLocalProperty('storage'),
                        'record' => $record->getIdentifier(),
                    ]
                );
                return null;
            }
            return $storages[$storageUid];
        }

        return $storages[0];
    }
}
