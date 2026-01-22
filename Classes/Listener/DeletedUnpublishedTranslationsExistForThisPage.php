<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Listener;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use In2code\In2publishCore\Component\Core\Reason\Reason;
use In2code\In2publishCore\Event\CollectReasonsWhyTheRecordIsNotPublishable;
use In2code\In2publishCore\CommonInjection\LocalDatabaseInjection;
use In2code\In2publishCore\CommonInjection\ForeignDatabaseInjection;

use function array_diff;
use function array_map;
use function implode;

/**
 * Prevents publishing a new page translation if there are deleted translations
 * (soft-deleted or hard-deleted/removed from DB) for the same language that
 * haven't been published yet.
 *
 * This prevents data inconsistency where foreign would have two translations
 * for the same language (the old one that wasn't removed, and the new one).
 */
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

        $languageParentId = $recordLanguageParent->getId();

        $this->checkSoftDeletedTranslations($event, $recordTable, $languageParentId, $recordLanguage, $currentRecord, $deleteField);

        $localProps = $record->getLocalProps();
        $foreignProps = $record->getForeignProps();
        if (!empty($localProps) && empty($foreignProps)) {
            $this->checkHardDeletedTranslations($event, $recordTable, $languageParentId, $recordLanguage);
        }
    }

    /**
     * Check for soft-deleted translations: records with deleted=1 on local
     * that still exist with deleted=0 on foreign.
     */
    private function checkSoftDeletedTranslations(
        CollectReasonsWhyTheRecordIsNotPublishable $event,
        string $recordTable,
        int $languageParentId,
        int $recordLanguage,
        int $currentRecord,
        ?string $deleteField
    ): void {
        if (null === $deleteField) {
            return;
        }

        $recordPid = $event->getRecord()->getProp('pid') ?? 0;

        // Find all soft-deleted translations for this page using same language on local
        $localQuery = $this->localDatabase->createQueryBuilder();
        $localQuery->getRestrictions()->removeAll();
        $deletedTranslationsLocal = $localQuery->select('uid')
            ->from($recordTable)
            ->where(
                $localQuery->expr()->eq('pid', $localQuery->createNamedParameter($recordPid, ParameterType::INTEGER)),
                $localQuery->expr()->eq($deleteField, 1),
                $localQuery->expr()->eq(
                    'l10n_parent',
                    $localQuery->createNamedParameter($languageParentId, ParameterType::INTEGER)
                ),
                $localQuery->expr()->eq(
                    'sys_language_uid',
                    $localQuery->createNamedParameter($recordLanguage, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $localQuery->expr()->neq('uid', $localQuery->createNamedParameter($currentRecord, ParameterType::INTEGER)),
            )
            ->executeQuery()
            ->fetchFirstColumn();

        if (empty($deletedTranslationsLocal)) {
            return;
        }

        // Check which of these deleted translations still exist on foreign (not deleted)
        $foreignQuery = $this->foreignDatabase->createQueryBuilder();
        $foreignQuery->getRestrictions()->removeAll();
        $unpublishedDeletedTranslations = $foreignQuery->select('uid')
            ->from($recordTable)
            ->where(
                $foreignQuery->expr()->in(
                    'uid',
                    $foreignQuery->createNamedParameter($deletedTranslationsLocal, ArrayParameterType::INTEGER)
                ),
                $foreignQuery->expr()->eq($deleteField, 0)
            )
            ->executeQuery()
            ->fetchFirstColumn();

        if (!empty($unpublishedDeletedTranslations)) {
            $event->addReason(
                new Reason(
                    'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:record.reason.unpublished_deleted_translations_for_this_page',
                    [
                        (string)$event->getRecord()->getId(),
                        $event->getRecord()->getClassification(),
                        implode(', ', $unpublishedDeletedTranslations),
                    ],
                    [
                        (string)$event->getRecord()->getId(),
                        $event->getRecord()->getClassification(),
                        implode(', ', $unpublishedDeletedTranslations),
                    ],
                ),
            );
        }
    }

    /**
     * Check for hard-deleted translations: records that exist on foreign
     * but have been completely removed from the local database.
     */
    private function checkHardDeletedTranslations(
        CollectReasonsWhyTheRecordIsNotPublishable $event,
        string $recordTable,
        int $languageParentId,
        int $recordLanguage
    ): void {
        // Find all non-deleted translations on foreign with the same l10n_parent and language
        $foreignQuery = $this->foreignDatabase->createQueryBuilder();
        $foreignQuery->getRestrictions()->removeAll();
        $foreignTranslations = $foreignQuery->select('uid')
            ->from($recordTable)
            ->where(
                $foreignQuery->expr()->eq(
                    'l10n_parent',
                    $foreignQuery->createNamedParameter($languageParentId, ParameterType::INTEGER)
                ),
                $foreignQuery->expr()->eq(
                    'sys_language_uid',
                    $foreignQuery->createNamedParameter($recordLanguage, ParameterType::INTEGER)
                ),
                $foreignQuery->expr()->eq('deleted', 0)
            )
            ->executeQuery()
            ->fetchFirstColumn();

        if (empty($foreignTranslations)) {
            return;
        }

        // Check which of these translations do NOT exist on local (hard-deleted)
        $localQuery = $this->localDatabase->createQueryBuilder();
        $localQuery->getRestrictions()->removeAll();
        $existingOnLocal = $localQuery->select('uid')
            ->from($recordTable)
            ->where(
                $localQuery->expr()->in(
                    'uid',
                    $localQuery->createNamedParameter($foreignTranslations, ArrayParameterType::INTEGER)
                )
            )
            ->executeQuery()
            ->fetchFirstColumn();

        // Hard-deleted translations = exist on foreign but not on local
        $foreignTranslationsInt = array_map('intval', $foreignTranslations);
        $existingOnLocalInt = array_map('intval', $existingOnLocal);
        $hardDeletedTranslations = array_diff($foreignTranslationsInt, $existingOnLocalInt);

        if (!empty($hardDeletedTranslations)) {
            $event->addReason(
                new Reason(
                    'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:record.reason.hard_deleted_unpublished_translations_for_this_page',
                    [
                        (string)$event->getRecord()->getId(),
                        $event->getRecord()->getClassification(),
                        implode(', ', $hardDeletedTranslations),
                    ],
                    [
                        (string)$event->getRecord()->getId(),
                        $event->getRecord()->getClassification(),
                        implode(', ', $hardDeletedTranslations),
                    ],
                ),
            );
        }
    }
}