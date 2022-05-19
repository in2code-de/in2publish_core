<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\TcaHandling\PreProcessing\Service\DatabaseIdentifierQuotingService;
use In2code\In2publishCore\Component\TcaHandling\Resolver\Resolver;
use In2code\In2publishCore\Component\TcaHandling\Resolver\SelectMmResolver;
use In2code\In2publishCore\Component\TcaHandling\Service\RelevantTablesService;
use TYPO3\CMS\Core\Database\Connection;

class CategoryProcessor extends AbstractProcessor
{
    protected Connection $localDatabase;
    protected DatabaseIdentifierQuotingService $databaseIdentifierQuotingService;
    protected RelevantTablesService $relevantTablesService;
    protected string $type = 'category';

    public function injectLocalDatabase(Connection $localDatabase): void
    {
        $this->localDatabase = $localDatabase;
    }

    public function injectDatabaseIdentifierQuotingService(
        DatabaseIdentifierQuotingService $databaseIdentifierQuotingService
    ): void {
        $this->databaseIdentifierQuotingService = $databaseIdentifierQuotingService;
    }

    public function injectRelevantTablesService(RelevantTablesService $relevantTablesService): void
    {
        $this->relevantTablesService = $relevantTablesService;
    }

    protected function buildResolver(string $table, string $column, array $processedTca): ?Resolver
    {
        if ($this->relevantTablesService->isEmptyOrExcludedTable('sys_category')) {
            return null;
        }
        $quotedTable = $this->localDatabase->quote($table);

        $additionalWhere = '{#sys_category}.{#sys_language_uid} IN (-1, 0)
             AND {#sys_category_record_mm}.{#fieldname} = "categories"
             AND {#sys_category_record_mm}.{#tablenames} = ' . $quotedTable;
        $additionalWhere = $this->databaseIdentifierQuotingService->dododo($additionalWhere);

        /** @var SelectMmResolver $resolver */
        $resolver = $this->container->get(SelectMmResolver::class);
        $resolver->configure(
            $additionalWhere,
            $column,
            'sys_category_record_mm',
            'sys_category',
            'uid_foreign'
        );
        return $resolver;
    }
}
