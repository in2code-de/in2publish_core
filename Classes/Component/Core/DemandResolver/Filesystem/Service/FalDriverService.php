<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service;

use In2code\In2publishCore\CommonInjection\FlexFormServiceInjection;
use In2code\In2publishCore\CommonInjection\LocalDatabaseInjection;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Driver\DriverRegistry;
use TYPO3\CMS\Core\Resource\ResourceStorageInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FalDriverService
{
    use LocalDatabaseInjection;
    use FlexFormServiceInjection;

    protected DriverRegistry $driverRegistry;
    protected array $rtc = [];

    /**
     * @codeCoverageIgnore
     * @noinspection PhpUnused
     */
    public function __construct(\TYPO3\CMS\Core\Resource\Driver\DriverRegistry $driverRegistry)
    {
        $this->driverRegistry = $driverRegistry;
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
                $result = $query->executeQuery();
                $storageRow = $result->fetchAssociative();
                $driver = $this->createFalDriver($storageRow);
            }
            $this->rtc[$storage] = $driver;
        }
        return $this->rtc[$storage];
    }

    protected function createFalDriver(array $storage): DriverInterface
    {
        $storageConfiguration = $this->convertFlexFormDataToConfigurationArray($storage['configuration'] ?? []);
        $driver = $this->getDriverObject($storage['driver'], $storageConfiguration);
        $driver->setStorageUid($storage['uid']);

        $capabilities = new \TYPO3\CMS\Core\Resource\Capabilities();

        if ($storage['is_browsable'] ?? null) {
            $capabilities->addCapabilities(\TYPO3\CMS\Core\Resource\Capabilities::CAPABILITY_BROWSABLE);
        }
        if ($storage['is_public'] ?? null) {
            $capabilities->addCapabilities(\TYPO3\CMS\Core\Resource\Capabilities::CAPABILITY_PUBLIC);
        }
        if ($storage['is_writable'] ?? null) {
            $capabilities->addCapabilities(\TYPO3\CMS\Core\Resource\Capabilities::CAPABILITY_WRITABLE);
        }
        $capabilities->addCapabilities(\TYPO3\CMS\Core\Resource\Capabilities::CAPABILITY_HIERARCHICAL_IDENTIFIERS);

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
