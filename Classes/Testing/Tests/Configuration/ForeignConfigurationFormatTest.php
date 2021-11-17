<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Tests\Configuration;

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

use In2code\In2publishCore\Command\Foreign\Status\ConfigFormatTestCommand;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Testing\Tests\Application\ForeignInstanceTest;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_merge;
use function base64_decode;
use function json_decode;
use function strpos;

class ForeignConfigurationFormatTest implements TestCaseInterface
{
    protected RemoteCommandDispatcher $remoteCommandDispatcher;

    public function __construct(RemoteCommandDispatcher $remoteCommandDispatcher)
    {
        $this->remoteCommandDispatcher = $remoteCommandDispatcher;
    }

    public function run(): TestResult
    {
        $request = new RemoteCommandRequest(ConfigFormatTestCommand::IDENTIFIER);
        $response = $this->remoteCommandDispatcher->dispatch($request);
        $errors = $response->getErrors();
        $output = $response->getOutput();
        $token = $this->tokenizeResponse($output);

        if (isset($token['Config Format Test']) && $response->isSuccessful()) {
            $testResults = json_decode(base64_decode($token['Config Format Test']), true);
            if (empty($testResults)) {
                return new TestResult('configuration.foreign_format_okay');
            }
            return new TestResult('configuration.foreign_format_error', TestResult::ERROR, $testResults);
        }

        $messages = array_merge($errors, $output);
        return new TestResult('configuration.foreign_format_test_exec_error', TestResult::ERROR, $messages);
    }

    protected function tokenizeResponse(array $output): array
    {
        $values = [];
        foreach ($output as $line) {
            if (false !== strpos($line, ':')) {
                [$key, $value] = GeneralUtility::trimExplode(':', $line);
                $values[$key] = $value;
            }
        }
        return $values;
    }

    public function getDependencies(): array
    {
        return [
            ForeignInstanceTest::class,
        ];
    }
}
