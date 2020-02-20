<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Testing\Tests\Configuration;

use In2code\In2publishCore\Command\StatusCommandController;
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
    public function run(): TestResult
    {
        $rceDispatcher = GeneralUtility::makeInstance(RemoteCommandDispatcher::class);
        $request = GeneralUtility::makeInstance(
            RemoteCommandRequest::class,
            StatusCommandController::CONFIG_FORMAT_TEST
        );
        $response = $rceDispatcher->dispatch($request);
        $errors = $response->getErrors();
        $token = $this->tokenizeResponse($response->getOutput());

        if ($response->isSuccessful()) {
            if (isset($token['Config Format Test'])) {
                $testResults = json_decode(base64_decode($token['Config Format Test']), true);
                if (empty($testResults)) {
                    return new TestResult('configuration.foreign_format_okay');
                }
                return new TestResult('configuration.foreign_format_error', TestResult::ERROR, $testResults);
            }
        }

        $messages = array_merge($errors, $token);
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
