<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\ConfigContainer\Provider;

/*
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
 */

use In2code\In2publishCore\Component\ConfigContainer\Cache\EarlyCacheInjection;
use In2code\In2publishCore\Service\Context\ContextServiceInjection;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Spyc;
use Symfony\Component\Yaml\Parser;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function file_exists;
use function file_get_contents;
use function hash_file;
use function rtrim;
use function str_replace;
use function strpos;
use function substr;
use function trigger_error;
use function var_export;

use const E_USER_DEPRECATED;

class FileProvider implements ProviderServiceInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use ContextServiceInjection;
    use EarlyCacheInjection;

    protected const DEPRECATION_CONFIG_PATH_TYPO3CONF = 'Storing the content publisher config file in typo3conf is deprecated and considered insecure. Please consider storing your config in the TYPO3\'s config folder.';
    protected array $extConf;

    /**
     * @codeCoverageIgnore
     * @noinspection PhpUnused
     */
    public function injectExtensionConfiguration(ExtensionConfiguration $extensionConfiguration): void
    {
        $this->extConf = $extensionConfiguration->get('in2publish_core');
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function getConfig(): array
    {
        $file = $this->getResolvedFilePath() . $this->contextService->getContext() . 'Configuration.yaml';
        if (!file_exists($file)) {
            $this->logger->notice('Config file for FileProvider does not exist', ['file' => $file]);
            return [];
        }

        $cacheKey = 'config_file_provider_' . hash_file('sha1', $file);
        if (!$this->earlyCache->has($cacheKey)) {
            $yamlContents = file_get_contents($file);
            $yamlContents = str_replace(['groups: *', '---'], ['groups: "*"', ''], $yamlContents);
            $yaml = new Parser();
            $config = $yaml->parse($yamlContents);
            $code = 'return ' . var_export($config, true) . ';';
            $this->earlyCache->flushByTag('config_file_provider');
            $this->earlyCache->set($cacheKey, $code, ['config_file_provider']);
        }

        return $this->earlyCache->require($cacheKey);
    }

    public function getPriority(): int
    {
        return 20;
    }

    protected function getResolvedFilePath(): string
    {
        $path = $this->extConf['pathToConfiguration'] ?? 'CONF:in2publish_core';

        if (false !== strpos($path, 'typo3conf/')) {
            trigger_error(self::DEPRECATION_CONFIG_PATH_TYPO3CONF, E_USER_DEPRECATED);
        }

        if (0 === strpos($path, 'CONF:')) {
            $path = Environment::getConfigPath() . '/' . substr($path, 5);
        } elseif (0 !== strpos($path, '/') && 0 !== strpos($path, '../')) {
            $path = GeneralUtility::getFileAbsFileName($path);
        } elseif (0 === strpos($path, '../')) {
            $path = Environment::getPublicPath() . '/' . $path;
        }
        return rtrim($path, '/') . '/';
    }
}
