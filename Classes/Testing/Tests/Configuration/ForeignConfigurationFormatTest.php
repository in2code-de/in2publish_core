<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Tests\Configuration;

use In2code\In2publishCore\Command\Status\ConfigFormatTestCommand;
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
    /** @var RemoteCommandDispatcher */
    protected $remoteCommandDispatcher;

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

        if ($response->isSuccessful()) {
            if (isset($token['Config Format Test'])) {
                $testResults = json_decode(base64_decode($token['Config Format Test']), true);
                if (empty($testResults)) {
                    return new TestResult('configuration.foreign_format_okay');
                }
                return new TestResult('configuration.foreign_format_error', TestResult::ERROR, $testResults);
            }
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
