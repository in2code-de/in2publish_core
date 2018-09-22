<?php
namespace In2code\In2publishCore\Communication\RemoteCommandExecution;

/***************************************************************
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
 ***************************************************************/

use In2code\In2publishCore\Config\ConfigContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Wrapper for a callable command (commands are the string after "./typo3/cli_dispatch.phpsh").
 */
class RemoteCommandRequest
{
    /**
     * @var bool
     */
    protected $usePhp = true;

    /**
     * @var string
     */
    protected $pathToPhp = '';

    /**
     * @var string
     */
    protected $workingDirectory = '';

    /**
     * @var array
     */
    protected $environmentVariables;

    /**
     * @var string
     */
    protected $dispatcher;

    /**
     * @var string
     */
    protected $command = '';

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * RemoteCommandRequest constructor.
     *
     * @param string $command
     * @param array $arguments
     * @param array $options
     */
    public function __construct($command = '', array $arguments = [], array $options = [])
    {
        $configContainer = GeneralUtility::makeInstance(ConfigContainer::class);
        $this->pathToPhp = $configContainer->get('foreign.pathToPhp');
        $this->workingDirectory = $configContainer->get('foreign.rootPath');
        $this->environmentVariables = [
            'TYPO3_CONTEXT' => $configContainer->get('foreign.context'),
            'IN2PUBLISH_CONTEXT' => 'Foreign',
        ];
        $this->dispatcher = './../vendor/bin/typo3';
        $this->command = $command;
        $this->arguments = $arguments;
        $this->options = $options;
    }

    /**
     * @param bool $usePhp
     */
    public function usePhp($usePhp)
    {
        $this->usePhp = (bool)$usePhp;
    }

    /**
     * @return string
     */
    public function getPathToPhp()
    {
        return $this->usePhp ? $this->pathToPhp : '';
    }

    /**
     * @return string
     */
    public function getWorkingDirectory()
    {
        return $this->workingDirectory;
    }

    /**
     * @return array
     */
    public function getEnvironmentVariables()
    {
        return $this->environmentVariables;
    }

    /**
     * @return string
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @param string $dispatcher
     */
    public function setDispatcher($dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param array $environmentVariables
     */
    public function setEnvironmentVariables($environmentVariables)
    {
        $this->environmentVariables = $environmentVariables;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @param string $command
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /**
     * @return bool
     */
    public function hasArguments()
    {
        return !empty($this->arguments);
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param array $arguments
     */
    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function setArgument($name, $value)
    {
        $this->arguments[$name] = $value;
    }

    /**
     * @return bool
     */
    public function hasOptions()
    {
        return !empty($this->options);
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = [];
        foreach ($options as $option) {
            $this->options[$option] = $option;
        }
    }

    /**
     * @param string $value
     */
    public function setOption($value)
    {
        $this->options[$value] = $value;
    }
}
