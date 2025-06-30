<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Service\Context;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
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

use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Tests\UnitTestCase;
use LogicException;
use PHPUnit\Framework\Attributes\CoversMethod;
use ReflectionProperty;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;

use function putenv;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
#[CoversMethod(ContextService::class, '__construct')]
#[CoversMethod(ContextService::class, 'determineContext')]
#[CoversMethod(ContextService::class, 'getContext')]
#[CoversMethod(ContextService::class, 'isLocal')]
#[CoversMethod(ContextService::class, 'isForeign')]
#[CoversMethod(ContextService::class, 'isContextDefined')]
class ContextServiceTest extends UnitTestCase
{
    private function setRedirectedIn2publishContext($value): void
    {
        $this->setIn2publishContext(null);
        if (null === $value) {
            putenv(ContextService::REDIRECT_ENV_VAR_NAME);
        } else {
            putenv(ContextService::REDIRECT_ENV_VAR_NAME . '=' . $value);
        }
    }

    private function setIn2publishContext($value): void
    {
        if (null === $value) {
            putenv(ContextService::ENV_VAR_NAME);
        } else {
            putenv(ContextService::ENV_VAR_NAME . '=' . $value);
        }
    }

    private function setApplicationContext($value)
    {
        if (null === $value) {
            putenv('TYPO3_CONTEXT');
            $applicationContext = new ApplicationContext('Production');
        } else {
            putenv('TYPO3_CONTEXT=' . $value);
            $applicationContext = new ApplicationContext($value);
        }

        $environmentReflection = new ReflectionProperty(Environment::class, 'context');
        $environmentReflection->setAccessible(true);
        $environmentReflection->setValue(Environment::class, $applicationContext);
    }

    public function testDefaultContextIsForeign(): void
    {
        $this->setIn2publishContext(null);

        $contextService = new ContextService();
        $this->assertSame(ContextService::FOREIGN, $contextService->getContext());
    }

    public function testContextIsDeterminedByEnvironmentVariable()
    {
        $this->setIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertSame(ContextService::LOCAL, $contextService->getContext());
    }

    public function testUnsupportedContextWillThrowAnExceptionIfApplicationContextIsNotProduction()
    {
        $this->setIn2publishContext('Wrong');
        $this->setApplicationContext('Development');

        $this->expectException(LogicException::class);
        $this->expectExceptionCode(1469717011);
        $this->expectExceptionMessage('The defined in2publish context is not supported');

        new ContextService();
    }

    public function testUnsupportedContextResultsInDefaultContextIfApplicationContextIsProduction()
    {
        $this->setIn2publishContext('Wrong');
        $this->setApplicationContext('Production');

        $contextService = new ContextService();
        $this->assertSame(ContextService::FOREIGN, $contextService->getContext());
    }

    public function testIsLocalReturnsTrueIfIn2publishContextIsLocal()
    {
        $this->setIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertTrue($contextService->isLocal());
    }

    public function testIsLocalReturnsFalseIfIn2publishContextIsNotLocal()
    {
        $this->setIn2publishContext(ContextService::FOREIGN);

        $contextService = new ContextService();
        $this->assertFalse($contextService->isLocal());
    }

    public function testIsForeignReturnsTrueIfIn2publishContextIsForeign()
    {
        $this->setIn2publishContext(ContextService::FOREIGN);

        $contextService = new ContextService();
        $this->assertTrue($contextService->isForeign());
    }

    public function testIsForeignReturnsFalseIfIn2publishContextIsLocal()
    {
        $this->setIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertFalse($contextService->isForeign());
    }

    public function testIsContextDefinedReturnsFalseIfContextIsNotDefined()
    {
        $this->setIn2publishContext(null);

        $contextService = new ContextService();
        $this->assertFalse($contextService->isContextDefined());
    }

    public function testIsContextDefinedReturnsTrueIfContextIsDefined()
    {
        $this->setIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertTrue($contextService->isContextDefined());
    }

    public function testRedirectContextAlsoDefinesContext()
    {
        $this->setRedirectedIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertTrue($contextService->isContextDefined());
    }

    public function testIsLocalReturnsTrueIfRedirectIn2publishContextIsLocal()
    {
        $this->setRedirectedIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertTrue($contextService->isLocal());
    }

    public function testIsForeignReturnsTrueIfRedirectIn2publishContextIsForeign()
    {
        $this->setRedirectedIn2publishContext(ContextService::FOREIGN);

        $contextService = new ContextService();
        $this->assertTrue($contextService->isForeign());
    }

    public function testIsLocalReturnsFalseIfRedirectIn2publishContextIsForeign()
    {
        $this->setRedirectedIn2publishContext(ContextService::FOREIGN);

        $contextService = new ContextService();
        $this->assertFalse($contextService->isLocal());
    }

    public function testIsForeignReturnsFalseIfRedirectIn2publishContextIsLocal()
    {
        $this->setRedirectedIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertFalse($contextService->isForeign());
    }
}
