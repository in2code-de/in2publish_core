<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Communication\RemoteCommandExecution;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
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

use In2code\In2publishCore\Config\ConfigContainer;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_merge;
use function defined;
use function file_exists;

use const TYPO3_COMPOSER_MODE;

/**
 * Wrapper for a callable command (commands are the string after "./vendor/bin/typo3").
 */
class RemoteCommandRequest
{
    protected bool $usePhp = true;

    protected string $pathToPhp = '';

    protected string $workingDirectory = '';

    protected array $environmentVariables;

    protected string $dispatcher;

    protected string $command = '';

    protected array $arguments = [];

    protected array $options = [];

    /**
     * RemoteCommandRequest constructor.
     *
     * @param string $command
     * @param array $arguments
     * @param array $options
     */
    public function __construct(string $command = '', array $arguments = [], array $options = [])
    {
        $configContainer = GeneralUtility::makeInstance(ConfigContainer::class);
        $this->pathToPhp = $configContainer->get('foreign.pathToPhp');
        $this->workingDirectory = $configContainer->get('foreign.rootPath');
        $this->environmentVariables = array_merge(
            $configContainer->get('foreign.envVars'),
            [
                'TYPO3_CONTEXT' => $configContainer->get('foreign.context'),
                'IN2PUBLISH_CONTEXT' => 'Foreign',
            ]
        );
        $isComposerMode = defined('TYPO3_COMPOSER_MODE') && true === TYPO3_COMPOSER_MODE;
        $dispatcher = $configContainer->get('foreign.dispatcher');
        if ($dispatcher) {
            $this->dispatcher = $dispatcher;
        } elseif ($isComposerMode && file_exists(Environment::getPublicPath() . '/vendor/bin/typo3')) {
            $this->dispatcher = './vendor/bin/typo3';
        } elseif ($isComposerMode && file_exists(Environment::getPublicPath() . '/../vendor/bin/typo3')) {
            $this->dispatcher = './../vendor/bin/typo3';
        } else {
            $this->dispatcher = 'typo3/sysext/core/bin/typo3';
        }
        $this->command = $command;
        $this->arguments = $arguments;
        $this->options = $options;
    }

    public function usePhp(bool $usePhp): void
    {
        $this->usePhp = $usePhp;
    }

    public function getPathToPhp(): string
    {
        return $this->usePhp ? $this->pathToPhp : '';
    }

    public function getWorkingDirectory(): string
    {
        return $this->workingDirectory;
    }

    public function getEnvironmentVariables(): array
    {
        return $this->environmentVariables;
    }

    public function getDispatcher(): string
    {
        return $this->dispatcher;
    }

    public function setDispatcher(string $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    public function setEnvironmentVariables(array $environmentVariables): void
    {
        $this->environmentVariables = $environmentVariables;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function setCommand(string $command): void
    {
        $this->command = $command;
    }

    public function hasArguments(): bool
    {
        return !empty($this->arguments);
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function setArguments(array $arguments): void
    {
        $this->arguments = $arguments;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setArgument(string $name, $value): void
    {
        $this->arguments[$name] = $value;
    }

    public function hasOptions(): bool
    {
        return !empty($this->options);
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = [];
        foreach ($options as $option) {
            $this->options[$option] = $option;
        }
    }

    /**
     * @param scalar $value
     */
    public function setOption($value): void
    {
        $this->options[$value] = $value;
    }
}
