<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Publisher\Command;

use In2code\In2publishCore\Component\TcaHandling\FileHandling\Service\FalDriverService;
use In2code\In2publishCore\Component\TcaHandling\Publisher\FileRecordPublisher;
use In2code\In2publishCore\Service\Context\ContextService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\PathUtility;

use function array_keys;

class FilePublisherCommand extends Command
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
                $driver->addFile(
                    Environment::getVarPath() . '/tx_in2publishcore/' . $row['temp_identifier_hash'],
                    PathUtility::dirname($row['identifier']),
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
        }
        return Command::SUCCESS;
    }
}
