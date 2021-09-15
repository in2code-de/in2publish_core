<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Tests\Fal;

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

use In2code\In2publishCore\Testing\Tests\Database\LocalDatabaseTest;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use TYPO3\CMS\Core\Resource\ResourceFactory;

class DefaultStorageIsConfiguredTest implements TestCaseInterface
{
    /** @var ResourceFactory */
    protected $resourceFactory;

    public function __construct(ResourceFactory $resourceFactory)
    {
        $this->resourceFactory = $resourceFactory;
    }

    public function run(): TestResult
    {
        if (null === $this->resourceFactory->getDefaultStorage()) {
            return new TestResult('fal.default_storage.missing', TestResult::ERROR);
        }
        return new TestResult('fal.default_storage.configured');
    }

    public function getDependencies(): array
    {
        return [
            LocalDatabaseTest::class,
        ];
    }
}
