<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher;

use In2code\In2publishCore\CommonInjection\LocalDatabaseInjection;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\FalDriverService;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\ProcessedFileCleanupInstruction;
use TYPO3\CMS\Core\Database\Connection;

use function rtrim;

class ProcessedFileCleanupService
{
    use LocalDatabaseInjection;

    public function cleanup(ProcessedFileCleanupInstruction $instruction, FalDriverService $falDriverService): void
    {
        $queryBuilder = $this->localDatabase->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $constraints = [];
        foreach ($instruction->getFileIdentifiersForProcessedFileCleanup() as $identifier) {
            $constraints[] = $queryBuilder->expr()->eq(
                'original_file.identifier',
                $queryBuilder->createNamedParameter($identifier),
            );
        }
        foreach ($instruction->getFolderIdentifiersForProcessedFileCleanup() as $identifier) {
            $constraints[] = $queryBuilder->expr()->like(
                'original_file.identifier',
                $queryBuilder->createNamedParameter(
                    $queryBuilder->escapeLikeWildcards(rtrim($identifier, '/') . '/') . '%',
                ),
            );
        }
        if ($constraints === []) {
            return;
        }

        $rows = $queryBuilder
            ->select('processed.uid', 'processed.storage', 'processed.identifier')
            ->from('sys_file_processedfile', 'processed')
            ->innerJoin('processed', 'sys_file', 'original_file', 'original_file.uid = processed.original')
            ->where(
                $queryBuilder->expr()->eq(
                    'original_file.storage',
                    $queryBuilder->createNamedParameter($instruction->getStorage(), Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->or(...$constraints),
            )
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($rows as $row) {
            $driver = $falDriverService->getDriver((int)$row['storage']);
            if ($row['identifier'] !== '' && $driver->fileExists($row['identifier'])) {
                $driver->deleteFile($row['identifier']);
            }
            $this->localDatabase->delete(
                'sys_file_processedfile',
                ['uid' => (int)$row['uid']],
                ['uid' => Connection::PARAM_INT],
            );
        }
    }
}
