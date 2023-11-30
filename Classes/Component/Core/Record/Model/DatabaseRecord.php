<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Model;

class DatabaseRecord extends AbstractDatabaseRecord implements DatabaseEntityRecord
{
    protected int $id;
    /** @var array<string> */
    protected array $changedProps;

    public function __construct(string $table, int $id, array $localProps, array $foreignProps, array $ignoredProps)
    {
        $this->table = $table;
        $this->id = $id;

        $this->localProps = $localProps;
        $this->foreignProps = $foreignProps;
        $this->ignoredProps = $ignoredProps;

        $this->changedProps = $this->calculateChangedProps();
        $this->state = $this->calculateState();
        $this->dependencies = $this->calculateDependencies();
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
