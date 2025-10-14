<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Listener;

use Doctrine\DBAL\ArrayParameterType;
use In2code\In2publishCore\Component\Core\Reason\Reason;
use In2code\In2publishCore\Event\CollectReasonsWhyTheRecordIsNotPublishable;
use In2code\In2publishCore\CommonInjection\LocalDatabaseInjection;
use In2code\In2publishCore\CommonInjection\ForeignDatabaseInjection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DeletedUnpublishedTranslationsExistForThisPage
{
    use LocalDatabaseInjection;
    use ForeignDatabaseInjection;

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
        $deleteField = $GLOBALS['TCA'][$record->getClassification()]['ctrl']['delete'] ?? null;
        $isCurrentRecordDeleted = (int)$record->getProp($deleteField) === 1;

        if ($recordLanguage < 1 || null === $recordLanguageParent) {
            return;
        }

        // If the current record is deleted, it should be publishable
        if ($isCurrentRecordDeleted) {
            return;
        }

        // Find all deleted translations for this page using same language on local
        $localQuery = $this->localDatabase->createQueryBuilder();
        $localQuery->getRestrictions()->removeAll();
        $deletedTranslationsLocal = $localQuery->select('uid')
                                               ->from($recordTable)
                                               ->where(
                                                   $localQuery->expr()->eq('pid', $localQuery->createNamedParameter($recordPid, \PDO::PARAM_INT)),
                                                   $localQuery->expr()->eq($deleteField, 1),
                                                   $localQuery->expr()->eq(
                                                       'l10n_parent',
                                                       $localQuery->createNamedParameter($recordLanguageParent->getId(), \PDO::PARAM_INT)
                                                   ),
                                                   $localQuery->expr()->eq(
                                                       'sys_language_uid',
                                                       $localQuery->createNamedParameter($recordLanguage, \PDO::PARAM_INT)
                                                   )
                                               )
                                               ->andWhere(
                                                   $localQuery->expr()->neq('uid', $localQuery->createNamedParameter($currentRecord, \PDO::PARAM_INT)),
                                               )
                                               ->executeQuery()
                                               ->fetchAllAssociative();

        if (empty($deletedTranslationsLocal)) {
            return;
        }

        $deletedUids = array_column($deletedTranslationsLocal, 'uid');

        // Check which of these deleted translations exist on foreign
        $foreignQuery = $this->foreignDatabase->createQueryBuilder();
        $foreignQuery->getRestrictions()->removeAll();
        $deletedTranslationsForeign = $foreignQuery->select('uid', $deleteField)
                                                   ->from($recordTable)
                                                   ->where(
                                                       $foreignQuery->expr()->in(
                                                           'uid',
                                                           $foreignQuery->createNamedParameter($deletedUids, ArrayParameterType::INTEGER)
                                                       )
                                                   )
                                                   ->executeQuery()
                                                   ->fetchAllAssociative();

        $foreignUids = array_column($deletedTranslationsForeign, 'uid');

        // Find deleted translations that exist locally but not on foreign
        // These unpublished deletions should be published first
        // https://projekte.in2code.de/issues/72213
        $unpublishedDeletedTranslations = array_diff($deletedUids, $foreignUids);

        if (!empty($unpublishedDeletedTranslations)) {
            $event->addReason(
                new Reason(
                    'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:record.reason.unpublished_deleted_translations_for_this_page',
                    [
                        (string)$record->getId(),
                        $record->getClassification(),
                        implode(', ', $unpublishedDeletedTranslations)
                    ],
                    [
                        (string)$record->getId(),
                        $record->getClassification(),
                        implode(', ', $unpublishedDeletedTranslations)
                    ],
                ),
            );
        }
    }
}