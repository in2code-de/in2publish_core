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
use In2code\In2publishCore\Service\Extension\ExtensionServiceInjection;
use Spyc;

use function explode;
use function file_exists;
use function hash_file;
use function implode;
use function var_export;

class VersionedFileProvider extends FileProvider
{
    use ExtensionServiceInjection;
    use EarlyCacheInjection;

    public function getConfig(): array
    {
        $path = $this->getResolvedFilePath();

        $version = $this->extensionService->getExtensionVersion('in2publish_core');
        [$major, $minor, $patch] = explode('.', $version);
        $context = $this->contextService->getContext();
        $candidates = [
            $path . $context . 'Configuration_' . implode('.', [$major, $minor, $patch]) . '.yaml',
            $path . $context . 'Configuration_' . implode('.', [$major, $minor]) . '.yaml',
            $path . $context . 'Configuration_' . implode('.', [$major]) . '.yaml',
        ];

        foreach ($candidates as $file) {
            if (file_exists($file)) {
                $cacheKey = 'config_versioned_file_provider_' . hash_file('sha1', $file);
                if (!$this->earlyCache->has($cacheKey)) {
                    $this->loadSpycIfRequired();
                    $config = Spyc::YAMLLoad($file);
                    $code = 'return ' . var_export($config, true) . ';';
                    $this->earlyCache->flushByTag('config_versioned_file_provider');
                    $this->earlyCache->set($cacheKey, $code, ['config_versioned_file_provider']);
                }

                return $this->earlyCache->require($cacheKey);
            }
        }

        return [];
    }

    public function getPriority(): int
    {
        return 25;
    }
}
