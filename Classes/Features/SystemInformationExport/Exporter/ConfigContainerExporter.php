<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\SystemInformationExport\Exporter;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
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

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainerInjection;
use In2code\In2publishCore\Component\ConfigContainer\Dumper\ConfigContainerDumper;
use Throwable;
use TYPO3\CMS\Core\Utility\ArrayUtility;

class ConfigContainerExporter implements SystemInformationExporter
{
    use ConfigContainerInjection;

    private ConfigContainerDumper $configContainerDumper;

    public function __construct(ConfigContainerDumper $configContainerDumper)
    {
        $this->configContainerDumper = $configContainerDumper;
    }

    public function getUniqueKey(): string
    {
        return 'containerDump';
    }

    public function getInformation(): array
    {
        $full = $this->configContainer->getContextFreeConfig();
        $personal = $this->configContainer->get();

        $containerDump = $this->configContainerDumper->dump($this->configContainer);

        $protectedValues = [
            'foreign.database.password',
            'sshConnection.privateKeyPassphrase',
        ];
        foreach ($protectedValues as $protectedValue) {
            foreach ([&$full, &$personal] as &$cfgArray) {
                $this->maskValue($cfgArray, $protectedValue);
            }
            unset($cfgArray);
            foreach ($containerDump['providerServiceConfig'] as &$providers) {
                $config = &$providers['config'];
                $this->maskValue($config, $protectedValue);
            }
            unset($providers);
            foreach ($containerDump['legacyProviderConfig'] as &$providers) {
                $config = &$providers['config'];
                $this->maskValue($config, $protectedValue);
            }
            unset($providers);
        }

        return $containerDump;
    }

    public function maskValue(&$cfgArray, string $protectedValue): void
    {
        try {
            $value = ArrayUtility::getValueByPath($cfgArray, $protectedValue, '.');
            if (!empty($value)) {
                /** @noinspection SpellCheckingInspection */
                $value = 'xxxxxxxx (masked)';
                $cfgArray = ArrayUtility::setValueByPath($cfgArray, $protectedValue, $value, '.');
            }
        } catch (Throwable $e) {
            // Ignore errors from get/setValueByPath. They may occur, although they shouldn't.
        }
    }
}
