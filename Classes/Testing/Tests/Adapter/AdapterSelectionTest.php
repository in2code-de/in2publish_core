<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Tests\Adapter;

/*
 * Copyright notice
 *
 * (c) 2017 in2code.de and the following authors:
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

use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteAdapter\RemoteAdapterRegistry;
use In2code\In2publishCore\Component\TemporaryAssetTransmission\TransmissionAdapter\TransmissionAdapterRegistry;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use Throwable;

class AdapterSelectionTest implements TestCaseInterface
{
    protected RemoteAdapterRegistry $remoteAdapterRegistry;
    protected TransmissionAdapterRegistry $transmissionAdapterRegistry;

    /**
     * @codeCoverageIgnore
     * @noinspection PhpUnused
     */
    public function injectRemoteAdapterRegistry(RemoteAdapterRegistry $remoteAdapterRegistry): void
    {
        $this->remoteAdapterRegistry = $remoteAdapterRegistry;
    }

    /**
     * @codeCoverageIgnore
     * @noinspection PhpUnused
     */
    public function injectTransmissionAdapterRegistry(TransmissionAdapterRegistry $transmissionAdapterRegistry): void
    {
        $this->transmissionAdapterRegistry = $transmissionAdapterRegistry;
    }

    public function run(): TestResult
    {
        try {
            $this->remoteAdapterRegistry->createSelectedAdapter();
        } catch (Throwable $exception) {
            return new TestResult(
                'adapter.adapter_selection.missing_adapter_or_implementation',
                TestResult::ERROR,
                [
                    'remote',
                    (string)$exception,
                ],
            );
        }
        try {
            $this->transmissionAdapterRegistry->createSelectedAdapter();
        } catch (Throwable $exception) {
            return new TestResult(
                'adapter.adapter_selection.missing_adapter_or_implementation',
                TestResult::ERROR,
                [
                    'transmission',
                    (string)$exception,
                ],
            );
        }
        return new TestResult('adapter.adapter_selection.valid');
    }

    public function getDependencies(): array
    {
        return [];
    }
}
