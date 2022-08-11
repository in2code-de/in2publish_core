<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\FileHandling\Service;

use In2code\In2publishCore\CommonInjection\LocalDatabaseInjection;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Driver\DriverRegistry;
use TYPO3\CMS\Core\Resource\ResourceStorageInterface;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function in_array;

class FalDriverService
{
    use LocalDatabaseInjection;

    protected DriverRegistry $driverRegistry;
    protected FlexFormService $flexFormService;
    protected array $rtc = [];

    public function injectDriverRegistry(DriverRegistry $driverRegistry): void
    {
        $this->driverRegistry = $driverRegistry;
    }

    public function injectFlexFormService(FlexFormService $flexFormService): void
    {
        $this->flexFormService = $flexFormService;
    }

    public function getDriver(int $storage): DriverInterface
    {
        if (!isset($this->rtc[$storage])) {
            if (0 === $storage) {
                $driver = $this->createFallbackDriver();
            } else {
                $query = $this->localDatabase->createQueryBuilder();
                $query->getRestrictions()->removeAll();
                $query->select('*')
                      ->from('sys_file_storage')
                      ->where($query->expr()->eq('uid', $query->createNamedParameter($storage)));
                $result = $query->execute();
                $storageRow = $result->fetchAssociative();
                $driver = $this->createFalDriver($storageRow);
            }
            $this->rtc[$storage] = $driver;
        }
        return $this->rtc[$storage];
    }

    /**
     * @return array<int, DriverInterface>
     */
    public function getDrivers(array $storagesUids): array
    {
        $query = $this->localDatabase->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('*')
              ->from('sys_file_storage')
              ->where($query->expr()->in('uid', $storagesUids));
        $result = $query->execute();
        $storages = $result->fetchAllAssociative();

        $drivers = [];
        foreach ($storages as $storage) {
            $storageUid = $storage['uid'];
            if (!isset($this->rtc[$storageUid])) {
                $this->rtc[$storageUid] = $this->createFalDriver($storage);
            }
            $drivers[$storageUid] = $this->rtc[$storageUid];
        }

        if (in_array(0, $storagesUids)) {
            if (!isset($this->rtc[0])) {
                $this->rtc[0] = $this->createFallbackDriver();
            }
            $drivers[0] = $this->rtc[0];
        }

        return $drivers;
    }

    protected function createFalDriver(array $storage): DriverInterface
    {
        $storageConfiguration = $this->convertFlexFormDataToConfigurationArray($storage['configuration'] ?? []);
        $driver = $this->getDriverObject($storage['driver'], $storageConfiguration);
        $driver->setStorageUid($storage['uid']);

        $capabilities =
            ($storage['is_browsable'] ?? null ? ResourceStorageInterface::CAPABILITY_BROWSABLE : 0)
            | ($storage['is_public'] ?? null ? ResourceStorageInterface::CAPABILITY_PUBLIC : 0)
            | ($storage['is_writable'] ?? null ? ResourceStorageInterface::CAPABILITY_WRITABLE : 0)
            | ResourceStorageInterface::CAPABILITY_HIERARCHICAL_IDENTIFIERS;
        $driver->mergeConfigurationCapabilities($capabilities);
        $driver->processConfiguration();
        $driver->initialize();
        return $driver;
    }

    protected function createFallbackDriver(): DriverInterface
    {
        $driver = $this->getDriverObject('Local', [
            'basePath' => Environment::getPublicPath(),
            'pathType' => 'absolute',
        ]);
        $driver->setStorageUid(0);
        $driver->processConfiguration();
        $driver->initialize();
        return $driver;
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
