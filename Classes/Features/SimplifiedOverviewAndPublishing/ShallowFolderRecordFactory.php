<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\SimplifiedOverviewAndPublishing;

use In2code\In2publishCore\Component\FalHandling\Finder\Factory\FileIndexFactory;
use In2code\In2publishCore\Domain\Driver\RemoteFileAbstractionLayerDriver;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Utility\StorageDriverExtractor;
use LogicException;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ShallowFolderRecordFactory
{
    protected ResourceFactory $resourceFactory;

    protected DriverInterface $localDriver;

    protected RemoteFileAbstractionLayerDriver $foreignDriver;

    protected FileIndexFactory $fileIndexFactory;

    public function __construct(ResourceFactory $resourceFactory)
    {
        $this->resourceFactory = $resourceFactory;
    }

    public function processRecords(array $records): void
    {
        $sortedRecords = [];

        foreach ($records as $record) {
            $storageUid = (int)$record->getLocalProperty('storage');
            $sortedRecords[$storageUid][] = $record;
        }
        unset($storageUid);
        foreach ($sortedRecords as $storageUid => $recordList) {
            $this->initializeDependencies($storageUid);
            $sortedRecords[$storageUid] = $this->filterFileRecords($recordList);
        }
    }

    protected function initializeDependencies(int $storageIdentifier): void
    {
        if ($storageIdentifier === 0) {
            $localStorage = $this->resourceFactory->getDefaultStorage();
        } else {
            $localStorage = $this->resourceFactory->getStorageObject($storageIdentifier);
        }
        $this->localDriver = StorageDriverExtractor::getLocalDriver($localStorage);
        $this->foreignDriver = StorageDriverExtractor::getForeignDriver($localStorage);
        $this->fileIndexFactory = GeneralUtility::makeInstance(
            FileIndexFactory::class,
            $this->localDriver,
            $this->foreignDriver
        );
    }

    /**
     * @see \In2code\In2publishCore\Domain\Factory\FolderRecordFactory::filterFileRecords
     * @noinspection DuplicatedCode
     * @noinspection MissingOrEmptyGroupStatementInspection
     * @noinspection PhpStatementHasEmptyBodyInspection
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function filterFileRecords(array $files): array
    {
        $fileIdentifiers = [];
        foreach ($files as $file) {
            if ($file->hasForeignProperty('identifier')) {
                $foreignFileId = $file->getForeignProperty('identifier');
            } else {
                $foreignFileId = $file->getLocalProperty('identifier');
            }
            $fileIdentifiers[] = $foreignFileId;
        }

        // Fetch file information for all files at once to save time.
        $foreignFileExistence = $this->foreignDriver->filesExists($fileIdentifiers);

        foreach ($files as $index => $file) {
            $fdb = $file->foreignRecordExists();
            $ldb = $file->localRecordExists();

            if ($file->hasLocalProperty('identifier')) {
                $localFileId = $file->getLocalProperty('identifier');
            } else {
                $localFileId = $file->getForeignProperty('identifier');
            }
            if ($file->hasForeignProperty('identifier')) {
                $foreignFileId = $file->getForeignProperty('identifier');
            } else {
                $foreignFileId = $file->getLocalProperty('identifier');
            }

            $lfs = $this->localDriver->fileExists($localFileId);
            $ffs = $foreignFileExistence[$foreignFileId];

            if ($ldb && !$lfs && !$ffs && !$fdb) {
                // CODE: [0] OLDB; The file exists only in the local database. Ignore the orphaned DB record.
                unset($files[$index]);
                continue;
            }
            if (!$ldb && $lfs && !$ffs && !$fdb) {
                // CODE: [1] OLFS; Fixed earlier. See [4] OL
                throw new LogicException(
                    'The FAL case OLFS is impossible due to prior record transformation',
                    1475178450
                );
            }
            if (!$ldb && !$lfs && $ffs && !$fdb) {
                // CODE: [2] OFFS; Fixed earlier. See [9] OF
                throw new LogicException(
                    'The FAL case OFFS is impossible due to prior record transformation',
                    1475250513
                );
            }
            if (!$ldb && !$lfs && !$ffs && $fdb) {
                // CODE: [3] OFDB; The file exists only in the foreign database. Ignore the orphaned DB record.
                unset($files[$index]);
                continue;
            }
            if ($ldb && $lfs && !$ffs && !$fdb) {
                // CODE: [4] OL; Nothing to do here. The record exists only on local and will be displayed correctly.
            } elseif ($ldb && !$lfs && $ffs && !$fdb) {
                // CODE: [5] LDFF; Foreign disk file got indexed, local database record is ignored. See [9] OF.
                throw new LogicException(
                    'The FAL case LDFF is impossible due to prior record transformation',
                    1475252172
                );
            } elseif ($ldb && !$lfs && !$ffs && $fdb) {
                // CODE: [6] ODB; Both indices are orphaned. Ignore them. This might be a result of [12] NLFS
                unset($files[$index]);
                continue;
            } elseif (!$ldb && $lfs && $ffs && !$fdb) {
                // CODE: [7] OFS; Both disk files were indexed. See [14] ALL
                throw new LogicException(
                    'The FAL case OFS is impossible due to prior record transformation',
                    1475572486
                );
            } elseif (!$ldb && $lfs && !$ffs && $fdb) {
                // CODE: [8] LFFD. Ignored foreign database record, indexed local disk file. See [11] NFFS
                throw new LogicException(
                    'The FAL case LFFD is impossible due to prior record transformation',
                    1475573724
                );
            } elseif (!$ldb && !$lfs && $ffs && $fdb) {
                // CODE: [9] OF; Nothing to do here;
            } elseif ($ldb && $lfs && $ffs && !$fdb) {
                // CODE: [10] NFDB; Indexed the foreign file. See [14] ALL
                throw new LogicException(
                    'The FAL case NFDB is impossible due to prior record transformation',
                    1475576764
                );
            } elseif ($ldb && $lfs && !$ffs && $fdb) {
                // CODE: [11] NFFS; The foreign database record is orphaned and will be ignored.
                $file->setForeignProperties([])->setDirtyProperties()->calculateState();
            } elseif ($ldb && !$lfs && $ffs && $fdb) {
                // CODE: [12] NLFS; The local database record is orphaned and will be ignored.
                $file->setLocalProperties([])->setDirtyProperties()->calculateState();
            } elseif (!$ldb && $lfs && $ffs && $fdb) {
                // CODE: [13] NLDB; Indexed the local disk file. See [14] ALL
                throw new LogicException(
                    'The FAL case NLDB is impossible due to prior record transformation',
                    1475578482
                );
            } elseif ($ldb && $lfs && $ffs && $fdb) {
                // CODE: [14] ALL
                if (RecordInterface::RECORD_STATE_UNCHANGED === $file->getState()) {
                    // The database records are identical, but this does not necessarily reflect the reality on disk,
                    // because files might have changed in the file system without FAL noticing these changes.
                    $this->fileIndexFactory->updateFileIndexInfo($file, $localFileId, $foreignFileId);
                }
            } elseif (!$ldb && !$lfs && !$ffs && !$fdb) {
                // CODE: [15] NONE; The file exists nowhere. Ignore it.
                unset($files[$index]);
                continue;
            }
            $file->addAdditionalProperty('depth', 2);
            $file->addAdditionalProperty('isAuthoritative', true);
        }
        return $files;
    }
}
