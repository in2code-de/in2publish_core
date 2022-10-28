<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Model\Task;

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

use function array_key_exists;
use function explode;
use function is_array;
use function json_encode;
use function trim;

use const JSON_THROW_ON_ERROR;

/**
 * Any Task must inherit from this class. This AbstractTask works like a Template for Task execution strategy
 */
abstract class AbstractTask
{
    protected int $uid;
    protected array $configuration;
    protected ?DateTime $creationDate = null;
    protected ?DateTime $executionBegin = null;
    protected ?DateTime $executionEnd = null;
    /** @var array<string> */
    private array $messages = [];

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

    final protected function beforeExecute(): void
    {
        $this->executionBegin = new DateTime();
    }

    /**
     * @api Implement this in your Task
     */
    abstract protected function executeTask(): bool;

    final protected function afterExecute(): void
    {
        $this->executionEnd = new DateTime();
    }

    final public function getUid(): int
    {
        return $this->uid;
    }

    /**
     * @return mixed
     * @deprecated Tasks can access the configuration directly. Will be removed in v13.
     */
    final public function getConfiguration(string $path = '')
    {
        $config = $this->configuration;
        /** @noinspection DuplicatedCode */
        $path = trim($path, " \t\n\r\0\x0B.");
        if (!empty($path)) {
            foreach (explode('.', $path) as $key) {
                if (!is_array($config)) {
                    return null;
                }
                if (!array_key_exists($key, $config)) {
                    return null;
                }
                $config = $config[$key];
            }
        }
        return $config;
    }

    final public function setCreationDate(DateTime $creationDate): AbstractTask
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    final public function setExecutionBegin(DateTime $executionBegin = null): AbstractTask
    {
        $this->executionBegin = $executionBegin;
        return $this;
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

    final public function toArray(): array
    {
        $properties = [
            'task_type' => static::class,
            'configuration' => json_encode($this->configuration, JSON_THROW_ON_ERROR),
            'messages' => json_encode($this->messages, JSON_THROW_ON_ERROR),
        ];
        if ($this->executionBegin instanceof DateTime) {
            $properties['execution_begin'] = $this->executionBegin->format('Y-m-d H:i:s');
        }
        if ($this->executionEnd instanceof DateTime) {
            $properties['execution_end'] = $this->executionEnd->format('Y-m-d H:i:s');
        }
        return $properties;
    }
}
