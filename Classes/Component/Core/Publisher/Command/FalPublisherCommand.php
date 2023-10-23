<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher\Command;

use In2code\In2publishCore\CommonInjection\LocalDatabaseInjection;
use In2code\In2publishCore\Component\Core\FileHandling\Service\FalDriverServiceInjection;
use In2code\In2publishCore\Component\Core\Publisher\FileRecordPublisher;
use In2code\In2publishCore\Component\Core\Publisher\FolderRecordPublisher;
use In2code\In2publishCore\Service\Context\ContextServiceInjection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\PathUtility;

use function array_keys;
use function explode;

class FalPublisherCommand extends Command
{
    use LocalDatabaseInjection;
    use ContextServiceInjection;
    use FalDriverServiceInjection;

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
                $targetDir = trim(PathUtility::dirname($row['identifier']), '/') . '/';
                if (!$driver->folderExists($targetDir)) {
                    $folderName = PathUtility::basename($targetDir);
                    $parentFolder = PathUtility::dirname($targetDir);
                    $driver->createFolder($folderName, $parentFolder, true);
                }
                // only add file if it does not exist on foreign
                // otherwise FalPublisherExecutionFailedException is thrown because there is no more file in transient folder
                if (!$driver->fileExists($row['identifier'])) {
                    $driver->addFile(
                        Environment::getVarPath() . '/transient/' . $row['temp_identifier_hash'],
                        $targetDir,
                        PathUtility::basename(
                            $row['identifier'],
                            false,
                        ),
                    );
                }
            }
            if (FileRecordPublisher::A_UPDATE === $row['file_action']) {
                $driver->replaceFile(
                    $row['identifier'],
                    Environment::getVarPath() . '/transient/' . $row['temp_identifier_hash'],
                );
            }
            if (FileRecordPublisher::A_DELETE === $row['file_action']) {
                $driver->deleteFile($row['identifier']);
            }
            if (FileRecordPublisher::A_RENAME === $row['file_action']) {
                $newFolderName = PathUtility::dirname($row['identifier']);
                $newFileName = PathUtility::basename($row['identifier']);
                if (!$driver->folderExists($newFolderName)) {
                    $driver->createFolder($newFolderName);
                }
                $driver->moveFileWithinStorage($row['previous_identifier'], $newFolderName, $newFileName);
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
