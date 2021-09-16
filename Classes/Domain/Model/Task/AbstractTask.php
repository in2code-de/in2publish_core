<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Model\Task;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de and the following authors:
 * Alex Kellner <alexander.kellner@in2code.de>,
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use DateTime;
use In2code\In2publishCore\Utility\ArrayUtility;

/**
 * Any Task must inherit from this class. This AbstractTask works like a Template for Task execution strategy
 */
abstract class AbstractTask
{
    /** @var int */
    protected $uid;

    /** @var array */
    protected $configuration;

    /** @var DateTime|null */
    protected $creationDate;

    /** @var DateTime|null */
    protected $executionBegin;

    /** @var DateTime|null */
    protected $executionEnd;

    /** @var array<string> */
    private $messages = [];

    final public function __construct(array $configuration, int $uid = 0)
    {
        $this->configuration = $configuration;
        $this->uid = $uid;
    }

    final public function execute(): bool
    {
        $this->beforeExecute();
        $success = $this->executeTask();
        $this->afterExecute();
        return $success;
    }

    /**
     * @api Implement this in your Task
     */
    abstract protected function executeTask(): bool;

    final protected function beforeExecute(): void
    {
        $this->executionBegin = new DateTime();
    }

    final protected function afterExecute(): void
    {
        $this->executionEnd = new DateTime();
    }

    final public function getUid(): int
    {
        return $this->uid;
    }

    /** @return mixed */
    final public function getConfiguration(string $path = '')
    {
        if ($path) {
            return ArrayUtility::getValueByPath($this->configuration, $path);
        }
        return $this->configuration;
    }

    final public function getCreationDate(): ?DateTime
    {
        return $this->creationDate;
    }

    final public function setCreationDate(DateTime $creationDate): AbstractTask
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    final public function getExecutionBegin(): ?DateTime
    {
        return $this->executionBegin;
    }

    final public function getExecutionBeginForPersistence(): string
    {
        if ($this->executionBegin instanceof DateTime) {
            return $this->executionBegin->format('Y-m-d H:i:s');
        }
        return 'NULL';
    }

    final public function setExecutionBegin(DateTime $executionBegin = null): AbstractTask
    {
        $this->executionBegin = $executionBegin;
        return $this;
    }

    final public function getExecutionEnd(): ?DateTime
    {
        return $this->executionEnd;
    }

    final public function getExecutionEndForPersistence(): string
    {
        if ($this->executionEnd instanceof DateTime) {
            return $this->executionEnd->format('Y-m-d H:i:s');
        }
        return 'NULL';
    }

    /**
     * @param DateTime|null $executionEnd
     *
     * @return AbstractTask
     */
    final public function setExecutionEnd(DateTime $executionEnd = null): AbstractTask
    {
        $this->executionEnd = $executionEnd;
        return $this;
    }

    final public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @param array<string> $messages
     *
     * @return AbstractTask
     */
    final public function setMessages(array $messages): AbstractTask
    {
        $this->messages = [];
        foreach ($messages as $message) {
            $this->addMessage($message);
        }
        return $this;
    }

    final public function addMessage(string $string): void
    {
        $this->messages[] = $string;
    }
}
