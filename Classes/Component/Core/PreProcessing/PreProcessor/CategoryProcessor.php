<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\PreProcessing\Service\TcaEscapingMarkerService;
use In2code\In2publishCore\Component\Core\Resolver\Resolver;
use In2code\In2publishCore\Component\Core\Resolver\SelectMmResolver;
use TYPO3\CMS\Core\Database\Connection;

class CategoryProcessor extends AbstractProcessor
{
    protected Connection $localDatabase;
    protected TcaEscapingMarkerService $tcaEscapingMarkerService;
    protected string $type = 'category';

    public function injectLocalDatabase(Connection $localDatabase): void
    {
        $this->localDatabase = $localDatabase;
    }

    public function injectTcaEscapingMarkerService(TcaEscapingMarkerService $tcaEscapingMarkerService): void
    {
        $this->tcaEscapingMarkerService = $tcaEscapingMarkerService;
    }

    protected function buildResolver(string $table, string $column, array $processedTca): Resolver
    {
        $quotedTable = $this->localDatabase->quote($table);

        $additionalWhere = '{#sys_category}.{#sys_language_uid} IN (-1, 0)
             AND {#sys_category_record_mm}.{#fieldname} = "categories"
             AND {#sys_category_record_mm}.{#tablenames} = ' . $quotedTable;
        $additionalWhere = $this->tcaEscapingMarkerService->escapeMarkedIdentifier($additionalWhere);

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
