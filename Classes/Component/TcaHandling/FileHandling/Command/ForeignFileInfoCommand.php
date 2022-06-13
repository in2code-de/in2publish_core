<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\FileHandling\Command;

use In2code\In2publishCore\Component\TcaHandling\FileHandling\Service\LocalFileInfoService;
use In2code\In2publishCore\Service\Context\ContextService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\Connection;

class ForeignFileInfoCommand extends Command
{
    protected ContextService $contextService;
    protected LocalFileInfoService $localFileInfoService;
    protected Connection $localDatabase;

    public function injectContextService(ContextService $contextService): void
    {
        $this->contextService = $contextService;
    }

    public function injectLocalFileInfoService(LocalFileInfoService $localFileInfoService): void
    {
        $this->localFileInfoService = $localFileInfoService;
    }

    public function injectConnection(Connection $localDatabase): void
    {
        $this->localDatabase = $localDatabase;
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
              ->from('tx_in2publishcore_remotefaldriver_file')
              ->where($query->expr()->eq('request_token', $query->createNamedParameter($requestToken)));
        $result = $query->execute();
        $rows = $result->fetchAllAssociative();

        $files = [];

        foreach ($rows as $row) {
            $files[$row['storage_uid']][$row['identifier']] = [];
        }

        $files = $this->localFileInfoService->addFileInfoToFiles($files);

        foreach ($files as $storage => $identifiers) {
            foreach ($identifiers as $file) {
                if (!isset($file['props'])) {
                    continue;
                }
                $props = $file['props'];
                $this->localDatabase->update(
                    'tx_in2publishcore_remotefaldriver_file',
                    [
                        'tstamp' => $GLOBALS['EXEC_TIME'],
                        'attr_size' => $props['size'],
                        'attr_mimetype' => $props['mimetype'],
                        'attr_name' => $props['name'],
                        'attr_extension' => $props['extension'],
                        'attr_folder_hash' => $props['folder_hash'],
                    ],
                    [
                        'request_token' => $requestToken,
                        'storage_uid' => $storage,
                        'identifier_hash' => $props['identifier_hash'],
                    ]
                );
            }
        }

        return Command::SUCCESS;
    }
}
