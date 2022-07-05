<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Publisher\Command;

use In2code\In2publishCore\Component\TcaHandling\FileHandling\Service\FalDriverService;
use In2code\In2publishCore\Component\TcaHandling\Publisher\FileRecordPublisher;
use In2code\In2publishCore\Component\TcaHandling\Publisher\FolderRecordPublisher;
use In2code\In2publishCore\Service\Context\ContextService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\PathUtility;

use function array_keys;
use function explode;

class FalPublisherCommand extends Command
{
    protected ContextService $contextService;
    protected Connection $localDatabase;
    protected FalDriverService $falDriverService;

    public function injectContextService(ContextService $contextService): void
    {
        $this->contextService = $contextService;
    }

    public function injectLocalDatabase(Connection $localDatabase): void
    {
        $this->localDatabase = $localDatabase;
    }

    public function injectFalDriverService(FalDriverService $falDriverService): void
    {
        $this->falDriverService = $falDriverService;
    }

    public function isEnabled(): bool
    {
        return $this->contextService->isForeign();
    }

    protected function configure(): void
    {
        $this->addArgument('requestToken', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $requestToken = $input->getArgument('requestToken');

        $query = $this->localDatabase->createQueryBuilder();
        $query->select('*')
              ->from('tx_in2publishcore_filepublisher_task')
              ->where($query->expr()->eq('request_token', $query->createNamedParameter($requestToken)));
        $result = $query->execute();
        $rows = $result->fetchAllAssociative();

        $storages = [];

        foreach ($rows as $row) {
            $storages[$row['storage_uid']] = true;
        }
        $storages = array_keys($storages);
        if (empty($storages)) {
            return Command::SUCCESS;
        }

        $drivers = $this->falDriverService->getDrivers($storages);

        foreach ($rows as $row) {
            $storage = $row['storage_uid'];
            $driver = $drivers[$storage];
            if (FileRecordPublisher::A_INSERT === $row['file_action']) {
                $targetDir = PathUtility::dirname($row['identifier']);
                if (!$driver->folderExists($targetDir)) {
                    $folderName = PathUtility::basename($targetDir);
                    $parentFolder = PathUtility::dirname($targetDir);
                    $driver->createFolder($folderName, $parentFolder, true);
                }
                $driver->addFile(
                    Environment::getVarPath() . '/tx_in2publishcore/' . $row['temp_identifier_hash'],
                    $targetDir,
                    PathUtility::basename($row['identifier'])
                );
            }
            if (FileRecordPublisher::A_UPDATE === $row['file_action']) {
                $driver->replaceFile(
                    $row['identifier'],
                    Environment::getVarPath() . '/tx_in2publishcore/' . $row['temp_identifier_hash']
                );
            }
            if (FileRecordPublisher::A_DELETE === $row['file_action']) {
                $driver->deleteFile($row['identifier']);
            }
            if (FolderRecordPublisher::A_INSERT === $row['folder_action']) {
                $identifier = explode(':/', $row['identifier'])[1];
                $folderName = PathUtility::basename($identifier);
                $parentFolder = PathUtility::dirname($identifier);
                $driver->createFolder($folderName, $parentFolder, true);
            }
            if (FolderRecordPublisher::A_DELETE === $row['folder_action']) {
                $identifier = explode(':', $row['identifier'])[1];
                $driver->deleteFolder($identifier, true);
            }
        }
        return Command::SUCCESS;
    }
}
