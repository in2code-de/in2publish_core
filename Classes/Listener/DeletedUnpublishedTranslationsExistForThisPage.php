<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Listener;

use In2code\In2publishCore\Event\CollectReasonsWhyTheRecordIsNotPublishable;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DeletedUnpublishedTranslationsExistForThisPage
{
    public function __invoke(CollectReasonsWhyTheRecordIsNotPublishable $event): void
    {
        $record = $event->getRecord();
        $recordTable = $record->getClassification();

        if ('pages' !== $recordTable) {
            return;
        }

        $recordLanguage = $record->getLanguage();
        $recordLanguageParent = $record->getTranslationParent();
        $recordPid = $record->getProp('pid') ?? 0;
        $currentRecord = $record->getId();

        if ($recordLanguage < 1 || null === $recordLanguageParent) {
            return;
        }

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($recordTable);

        $query = $connection->createQueryBuilder();
        $query->getRestrictions()->removeAll(); // Remove all restrictions in order to find deleted pages
        $result = $query->select('*')
                        ->from($recordTable)
                        ->where(
                            $query->expr()->eq('pid', $query->createNamedParameter($recordPid, \PDO::PARAM_INT)),
                            $query->expr()->eq('deleted', 1),
                            $query->expr()->eq(
                                'l10n_parent',
                                $query->createNamedParameter($recordLanguageParent->getId(), \PDO::PARAM_INT)
                            ),
                            $query->expr()->eq(
                                'sys_language_uid',
                                $query->createNamedParameter($recordLanguage, \PDO::PARAM_INT)
                            )
                        )
                        ->andWhere(
                            $query->expr()->neq('uid', $query->createNamedParameter($currentRecord, \PDO::PARAM_INT)),
                        )
                        ->executeQuery()
                        ->fetchAllAssociative();

        if (empty($result)) {
            return;
        }
        $event->addReason(
            new \In2code\In2publishCore\Component\Core\Reason\Reason(
                'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:record.reason.unpublished_deleted_translations_for_this_page',
                [
                    (string)$record->getId(),
                    $record->getClassification(),
                ],
                [
                    (string)$record->getId(),
                    $record->getClassification(),
                ],
            ),
        );
    }
}