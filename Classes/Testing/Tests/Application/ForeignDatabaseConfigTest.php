<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Tests\Application;

/*
 * Copyright notice
 *
 * (c) 2019 in2code.de and the following authors:
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

use In2code\In2publishCore\Command\Foreign\Status\DbConfigTestCommand;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function base64_decode;
use function in_array;
use function is_array;
use function json_decode;
use function strpos;

class ForeignDatabaseConfigTest implements TestCaseInterface
{
    public const DB_CONFIG_TEST_TYPE = 'DB Config Test';

    /** @var RemoteCommandDispatcher */
    protected $rceDispatcher;

    /** @var Random */
    protected $random;

    public function __construct(RemoteCommandDispatcher $remoteCommandDispatcher, Random $random)
    {
        $this->rceDispatcher = $remoteCommandDispatcher;
        $this->random = $random;
    }

    public function run(): TestResult
    {
        $connection = DatabaseUtility::buildForeignDatabaseConnection();
        $connection->delete('tx_in2code_in2publish_task', ['task_type' => self::DB_CONFIG_TEST_TYPE]);
        $random = $this->random->generateRandomHexString(32);
        $row = ['task_type' => self::DB_CONFIG_TEST_TYPE, 'configuration' => $random];
        $connection->insert('tx_in2code_in2publish_task', $row);

        $request = new RemoteCommandRequest(DbConfigTestCommand::IDENTIFIER);
        $response = $this->rceDispatcher->dispatch($request);

        if ($response->isSuccessful()) {
            $result = $this->tokenizeResponse($response->getOutput());
            $configs = json_decode(base64_decode($result['DB Config']), true);
            if (is_array($configs) && in_array($random, $configs)) {
                $testResult = new TestResult('application.foreign_database_config.success');
            } else {
                $testResult = new TestResult(
                    'application.foreign_database_config.failure',
                    TestResult::ERROR,
                    ['application.foreign_database_config.configuration_invalid']
                );
            }
        } else {
            $testResult = new TestResult(
                'application.foreign_database_config.unexpected_error',
                TestResult::ERROR,
                [$response->getErrors(), $response->getOutput()]
            );
        }
        $connection->delete('tx_in2code_in2publish_task', ['task_type' => self::DB_CONFIG_TEST_TYPE]);
        return $testResult;
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
