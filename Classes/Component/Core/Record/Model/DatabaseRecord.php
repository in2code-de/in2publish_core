<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Model;

use function array_diff_assoc;
use function array_diff_key;
use function array_flip;
use function array_keys;

class DatabaseRecord extends AbstractDatabaseRecord implements DatabaseEntityRecord
{
    protected int $id;
    protected array $ignoredProps;
    protected array $changedProps;

    public function __construct(string $table, int $id, array $localProps, array $foreignProps, array $ignoredProps)
    {
        $this->table = $table;
        $this->id = $id;

        $this->localProps = $localProps;
        $this->foreignProps = $foreignProps;
        $this->ignoredProps = $ignoredProps;

        $relevantLocalProps = array_diff_key($this->localProps, array_flip($ignoredProps));
        $relevantForeignProps = array_diff_key($this->foreignProps, array_flip($ignoredProps));
        $this->changedProps = array_keys(array_diff_assoc($relevantLocalProps, $relevantForeignProps));

        $this->state = $this->calculateState();
    }

    public function isChanged(): bool
    {
        return !empty($this->changedProps);
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int The UID of the page this record is stored in. If this record is a page record, it returns its default
     *     language id.
     */
    public function getPageId(): int
    {
        if ('pages' === $this->table) {
            if ($this->getLanguage() > 0) {
                return $this->getTransOrigPointer();
            }

            return $this->id;
        }
        return $this->getProp('pid');
    }

    public function getChangedProps(): array
    {
        return $this->changedProps;
    }
}
