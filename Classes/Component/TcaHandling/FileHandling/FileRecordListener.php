<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\FileHandling;

use In2code\In2publishCore\Component\TcaHandling\Demands;
use In2code\In2publishCore\Component\TcaHandling\Repository\SingleDatabaseRepository;
use In2code\In2publishCore\Domain\Factory\RecordFactory;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Event\RecordWasCreated;
use InvalidArgumentException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Driver\DriverRegistry;
use TYPO3\CMS\Core\Resource\Exception\InvalidConfigurationException;
use TYPO3\CMS\Core\Resource\ResourceStorageInterface;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_keys;
use function in_array;
use function sprintf;

class FileRecordListener
{
    protected DriverRegistry $driverRegistry;
    protected FlexFormService $flexFormService;
    protected RecordFactory $recordFactory;
    protected SingleDatabaseRepository $localRepository;
    /**
     * @var list<Record>
     */
    protected array $fileRecords = [];

    public function injectDriverRegistry(DriverRegistry $driverRegistry): void
    {
        $this->driverRegistry = $driverRegistry;
    }

    public function injectLocalRepository(SingleDatabaseRepository $localRepository): void
    {
        $this->localRepository = $localRepository;
    }

    public function injectRecordFactory(RecordFactory $recordFactory): void
    {
        $this->recordFactory = $recordFactory;
    }

    public function injectFlexFormService(FlexFormService $flexFormService): void
    {
        $this->flexFormService = $flexFormService;
    }

    public function onRecordWasCreated(RecordWasCreated $event): void
    {
        $record = $event->getRecord();
        if ('sys_file' !== $record->getClassification()) {
            return;
        }
        $this->fileRecords[] = $record;
    }

    public function onRecordRelationsWereResolved(): void
    {
        $demands = new Demands();
        foreach ($this->fileRecords as $record) {
            $localIdentifier = $record->getLocalProps()['identifier'] ?? null;
            $localStorage = $record->getLocalProps()['storage'] ?? null;
            $foreignIdentifier = $record->getForeignProps()['identifier'] ?? null;
            $foreignStorage = $record->getForeignProps()['storage'] ?? null;
            if ($localStorage !== $foreignStorage || $localIdentifier !== $foreignIdentifier) {
                $demands->addFile($foreignStorage, $foreignIdentifier, $record);
            }
            $demands->addFile($localStorage, $localIdentifier, $record);
        }

        $files = $demands->getFiles();

        $storagesUids = array_keys($files);
        $storages = $this->localRepository->findByProperty('sys_file_storage', 'uid', $storagesUids);

        $drivers = [];
        if (in_array(0, $storagesUids)) {
            $driver = $this->getDriverObject('Local', [
                'basePath' => Environment::getPublicPath(),
                'pathType' => 'absolute',
            ]);
            $driver->setStorageUid(0);
            try {
                $driver->processConfiguration();
            } catch (InvalidConfigurationException $e) {
                // Configuration error
            }
            $driver->initialize();
            $drivers[0] = $driver;
        }

        foreach ($storages as $uid => $storage) {
            $storageConfiguration = $this->convertFlexFormDataToConfigurationArray($storage['configuration'] ?? []);
            $driver = $this->getDriverObject($storage['driver'], $storageConfiguration);
            $driver->setStorageUid($uid);

            $capabilities =
                ($storage['is_browsable'] ?? null ? ResourceStorageInterface::CAPABILITY_BROWSABLE : 0) |
                ($storage['is_public'] ?? null ? ResourceStorageInterface::CAPABILITY_PUBLIC : 0) |
                ($storage['is_writable'] ?? null ? ResourceStorageInterface::CAPABILITY_WRITABLE : 0) |
                // Always let the driver decide whether to set this capability
                ResourceStorageInterface::CAPABILITY_HIERARCHICAL_IDENTIFIERS;
            $driver->mergeConfigurationCapabilities($capabilities);
            try {
                $driver->processConfiguration();
            } catch (InvalidConfigurationException $e) {
                // Configuration error
            }
            $driver->initialize();
            $drivers[$uid] = $driver;
        }

        $fileRecords = [];

        foreach ($files as $storageUid => $filesFromStorage) {
            foreach ($filesFromStorage as $fileIdentifier => $record) {
                try {
                    $localProperties = $drivers[$storageUid]->getFileInfoByIdentifier($fileIdentifier);
                } catch (InvalidArgumentException $exception) {
                    continue;
                }
                $record = $this->recordFactory->createFileRecord($localProperties, []);
                $fileRecords[] = $record;
            }
        }

        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($fileRecords, __FILE__ . '@' . __LINE__, 20, false, true, false, [], []);die(__FILE__ . '@' . __LINE__);
    }

    protected function getDriverObject(string $driverIdentificationString, array $driverConfiguration): DriverInterface
    {
        $driverClass = $this->driverRegistry->getDriverClass($driverIdentificationString);
        /** @var DriverInterface $driverObject */
        $driverObject = GeneralUtility::makeInstance($driverClass, $driverConfiguration);
        return $driverObject;
    }

    protected function convertFlexFormDataToConfigurationArray(string $flexFormData): array
    {
        if ($flexFormData) {
            return $this->flexFormService->convertFlexFormContentToArray($flexFormData);
        }
        return [];
    }
}
