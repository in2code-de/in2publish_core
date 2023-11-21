<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Model;

use TYPO3\CMS\Core\Utility\PathUtility;

use function in_array;

class FileRecord extends AbstractRecord
{
    public const CLASSIFICATION = '_file';

    /** @var array<string> */
    protected array $changedProps;

    public function __construct(array $localProps, array $foreignProps, array $ignoredProps = [])
    {
        $this->localProps = $localProps;
        $this->foreignProps = $foreignProps;
        $this->ignoredProps = $ignoredProps;
        $this->changedProps = $this->calculateChangedProps();

        $this->state = $this->calculateState();
    }

    protected function calculateState(): string
    {
        $state = parent::calculateState();
        if (Record::S_CHANGED === $state) {
            $changedProps = $this->getChangedProps();
            // File contents changed!
            if (in_array('sha1', $changedProps, true)) {
                return Record::S_CHANGED;
            }
            // File was renamed
            if (in_array('name', $changedProps, true)) {
                return Record::S_CHANGED;
            }
            // File contents did not change but the folder
            if (in_array('folder_hash', $changedProps, true)) {
                return Record::S_MOVED;
            }
        }
        return $state;
    }

    public function getClassification(): string
    {
        return self::CLASSIFICATION;
    }

    public function getId(): string
    {
        return $this->getProp('storage') . ':' . $this->getProp('identifier');
    }

    public function getForeignIdentificationProps(): array
    {
        return [];
    }

    public function isMovedToDifferentFolder(): bool
    {
        if (!isset($this->localProps['identifier'], $this->foreignProps['identifier'])) {
            return false;
        }
        return PathUtility::dirname($this->localProps['identifier'])
            !== PathUtility::dirname($this->foreignProps['identifier']);
    }

    public function getChangedProps(): array
    {
        return $this->changedProps;
    }
}
