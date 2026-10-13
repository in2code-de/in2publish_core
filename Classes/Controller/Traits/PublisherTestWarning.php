<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Controller\Traits;

use In2code\In2publishCore\Service\Environment\EnvironmentService;
use In2code\In2publishCore\Testing\Tests\Database\FileUidSequenceTest;
use In2code\In2publishCore\Testing\Tests\TestResult;
use Psr\Http\Message\ResponseInterface;

use function array_diff;
use function in_array;

trait PublisherTestWarning
{
    protected function assignPublisherTestWarning(): void
    {
        $identifier = $this->environmentService->getTestWarningIdentifier();
        $data = $GLOBALS['BE_USER']->getModuleData('tx_in2publishcore_test_warning') ?? [];
        $accepted = ($data['accepted'] ?? false) === true;
        $enabled = $this->configContainer->get('features.foreignFileUidCheck.enable');
        $testStatus = $this->environmentService->getTestStatus();
        $fileUidWarning = null;
        if ($accepted) {
            $testStatus = array_diff($testStatus, [
                EnvironmentService::STATE_TESTS_CHANGED,
                EnvironmentService::STATE_TESTS_WARNING,
                EnvironmentService::STATE_TESTS_NEVER_RAN,
            ]);
        } elseif (!$enabled) {
            $testStatus = array_diff($testStatus, [EnvironmentService::STATE_TESTS_WARNING]);
        } else {
            $result = FileUidSequenceTest::check(true, false);
            if ($result->getSeverity() === TestResult::WARNING) {
                $fileUidWarning = $result;
            }
            if (
                $result->getSeverity() === TestResult::WARNING
                && !in_array(EnvironmentService::STATE_TESTS_WARNING, $testStatus, true)
            ) {
                $testStatus[] = EnvironmentService::STATE_TESTS_WARNING;
            }
        }
        $this->moduleTemplate->assignMultiple([
            'testStatus' => $testStatus,
            'testWarningIdentifier' => $identifier,
            'fileUidWarning' => $fileUidWarning,
        ]);
    }

    public function acceptTestWarningAction(string $identifier): ResponseInterface
    {
        if ($identifier === $this->environmentService->getTestWarningIdentifier()) {
            $GLOBALS['BE_USER']->pushModuleData('tx_in2publishcore_test_warning', ['accepted' => true]);
        }
        return $this->redirect('index');
    }
}
