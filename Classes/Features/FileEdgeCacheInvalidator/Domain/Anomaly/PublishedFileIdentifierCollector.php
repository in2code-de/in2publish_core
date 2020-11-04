<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\FileEdgeCacheInvalidator\Domain\Anomaly;

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Repository\TaskRepository;
use In2code\In2publishCore\Features\FileEdgeCacheInvalidator\Domain\Model\Task\FlushFileEdgeCacheTask;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_keys;
use function is_numeric;
use function sprintf;
use function strpos;

class PublishedFileIdentifierCollector implements SingletonInterface
{
    /** @var string[][] */
    protected $publishedRecords = [];

    /** @var int[] */
    protected $collectedRecords = [];

    /** @var TaskRepository */
    protected $taskRepository;

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct()
    {
        $this->taskRepository = GeneralUtility::makeInstance(TaskRepository::class);
    }

    /**
     * Collect all files during publishing.
     * Instead of collecting an array of combined identifiers (FAL), save the UID of one sys_file entry, that points to
     * the file. Combined identifiers are much longer than UIDs, so we save a lot of IO bytes.
     *
     * @param $table
     * @param Record $record
     * @return void|null
     */
    public function registerPublishedFile($table, Record $record): void
    {
        if ('sys_file' !== $table) {
            return;
        }

        $storage = $record->getLocalProperty('storage');
        if (!is_numeric($storage)) {
            $storage = $record->getForeignProperty('storage');
        }
        $identifier = $record->getMergedProperty('identifier');

        if (strpos($identifier, ',')) {
            [$identifier] = GeneralUtility::trimExplode(',', $identifier);
        }

        $combinedIdentifier = sprintf('%d:%s', $storage, $identifier);

        if (isset($this->publishedRecords[$table][$combinedIdentifier])) {
            return;
        }

        $this->collectedRecords[$record->getIdentifier()] = true;
    }

    public function writeFlushFileEdgeCacheTask(): void
    {
        if (empty($this->collectedRecords)) {
            return;
        }
        $this->taskRepository->add(new FlushFileEdgeCacheTask(array_keys($this->collectedRecords)));
    }
}
