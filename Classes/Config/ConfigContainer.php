<?php
namespace In2code\In2publishCore\Config;

/***************************************************************
 * Copyright notice
 *
 * (c) 2018 in2code.de and the following authors:
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

use In2code\In2publishCore\Config\Definer\DefinerInterface;
use In2code\In2publishCore\Config\Node\Node;
use In2code\In2publishCore\Config\Node\NodeCollection;
use In2code\In2publishCore\Config\Provider\ContextualProvider;
use In2code\In2publishCore\Config\Provider\ProviderInterface;
use In2code\In2publishCore\Utility\ArrayUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ConfigContainer
 */
class ConfigContainer implements SingletonInterface
{
    /**
     * @var array
     */
    protected $providers = [];

    /**
     * @var DefinerInterface[]
     */
    protected $definers = [];

    /**
     * @var array|null
     */
    protected $config = null;

    /**
     * @var NodeCollection[]|null[]
     */
    protected $definition = [
        'local' => null,
        'foreign' => null,
    ];

    /**
     * @param string $path
     * @return mixed
     */
    public function get($path = '')
    {
        $config = $this->getConfig();
        if (!empty($path)) {
            return ArrayUtility::getValueByPath($config, $path);
        }
        return $config;
    }

    /**
     * @return array
     */
    protected function getConfig()
    {
        if (null !== $this->config) {
            return $this->config;
        }
        $complete = true;
        $priority = [];
        foreach ($this->providers as $class => $config) {
            $provider = GeneralUtility::makeInstance($class);
            if (null === $config) {
                if ($provider instanceof ProviderInterface) {
                    if ($provider->isAvailable()) {
                        $this->providers[$class] = $provider->getConfig();
                        $priority[$class] = $provider->getPriority();
                    } else {
                        $complete = false;
                    }
                }
            } else {
                $priority[$class] = $provider->getPriority();
            }
        }

        asort($priority);

        $config = $this->processConfig($priority);

        if (true === $complete) {
            $this->config = $config;
        }

        return $config;
    }

    /**
     * Returns the configuration without any contextual parts.
     * Is always "fresh" but never guaranteed to be complete.
     *
     * @return array
     */
    public function getContextFreeConfig()
    {
        $priority = [];
        foreach ($this->providers as $class => $config) {
            $provider = GeneralUtility::makeInstance($class);
            if (!($provider instanceof ContextualProvider)) {
                if (null === $config) {
                    if ($provider instanceof ProviderInterface) {
                        if ($provider->isAvailable()) {
                            $this->providers[$class] = $provider->getConfig();
                            $priority[$class] = $provider->getPriority();
                        }
                    }
                } else {
                    $priority[$class] = $provider->getPriority();
                }
            }
        }

        $config = $this->processConfig($priority);

        return $config;
    }

    /**
     * Applies the configuration of each provider in order of priority.
     *
     * @param array $priority
     * @return array|array[]|bool[]|int[]|string[] Sorted, merged and type casted configuration.
     */
    protected function processConfig(array $priority)
    {
        asort($priority);

        $config = [];
        foreach (array_keys($priority) as $class) {
            $providerConfig = $this->providers[$class];
            \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($config, $providerConfig);
        }

        $config = $this->getLocalDefinition()->cast($config);

        return $config;
    }

    /**
     * @param string $path
     * @return Node|NodeCollection
     */
    public function getLocalDefinition($path = '')
    {
        if (null === $this->definition['local']) {
            $definition = GeneralUtility::makeInstance(NodeCollection::class);
            foreach ($this->definers as $class => $definer) {
                if ($definer === null) {
                    $this->definers[$class] = $definer = GeneralUtility::makeInstance($class);
                }
                if ($definer instanceof DefinerInterface) {
                    $definition->addNodes($definer->getLocalDefinition());
                }
            }
            $this->definition['local'] = $definition;
        }
        return $this->definition['local']->getNodePath($path);
    }

    /**
     * @param string $path
     * @return Node|NodeCollection
     */
    public function getForeignDefinition($path = '')
    {
        if (null === $this->definition['foreign']) {
            $definition = GeneralUtility::makeInstance(NodeCollection::class);
            foreach ($this->definers as $class => $definer) {
                if ($definer === null) {
                    $this->definers[$class] = $definer = GeneralUtility::makeInstance($class);
                }
                if ($definer instanceof DefinerInterface) {
                    $definition->addNodes($definer->getForeignDefinition());
                }
            }
            $this->definition['foreign'] = $definition;
        }
        return $this->definition['foreign']->getNodePath($path);
    }

    /**
     * All providers must be registered in ext_localconf.php!
     * Providers registered in ext_tables.php will not overrule configurations of already loaded extensions.
     * Providers must implement the ProviderInterface or they won't be called.
     *
     * @param string $provider
     */
    public function registerProvider($provider)
    {
        $this->providers[$provider] = null;
    }

    /**
     * All definers must be registered in ext_localconf.php!
     * Definers must implement the DefinerInterface or they won't be called.
     *
     * @param string $definer
     */
    public function registerDefiner($definer)
    {
        $this->definers[$definer] = null;
    }
}
