<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Tests\Helper;

use Codeception\Module;
use In2code\In2publishCore\Service\Context\ContextService;
use ReflectionProperty;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class In2publishCore extends Module
{
    public function clearIn2publishContext()
    {
        $this->setIn2publishContext(null);
        $this->setRedirectedIn2publishContext(null);
    }

    public function setIn2publishContext($value)
    {
        if (null === $value) {
            putenv(ContextService::ENV_VAR_NAME);
        } else {
            putenv(ContextService::ENV_VAR_NAME . '=' . $value);
        }
    }

    public static function setRedirectedIn2publishContext($value)
    {
        if (null === $value) {
            putenv(ContextService::REDIRECT_ENV_VAR_NAME);
        } else {
            putenv(ContextService::REDIRECT_ENV_VAR_NAME . '=' . $value);
        }
    }

    public function setApplicationContext($value)
    {
        if (null === $value) {
            putenv('TYPO3_CONTEXT');
            $applicationContext = new ApplicationContext('Production');
        } else {
            putenv('TYPO3_CONTEXT=' . $value);
            $applicationContext = new ApplicationContext($value);
        }
        $this->setStaticProperty(GeneralUtility::class, 'applicationContext', $applicationContext);
    }

    public function setStaticProperty(string $class, string $name, $value)
    {
        $reflectionProperty = new ReflectionProperty($class, $name);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($value);
    }
}
