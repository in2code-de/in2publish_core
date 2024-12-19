<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Tests\Application;

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

use In2code\In2publishCore\Command\Foreign\Status\ShortSiteConfigurationCommand;
use In2code\In2publishCore\CommonInjection\ForeignDatabaseInjection;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandDispatcherInjection;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Testing\Tests\Adapter\RemoteAdapterTest;
use In2code\In2publishCore\Testing\Tests\Database\ForeignDatabaseTest;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_column;
use function array_combine;
use function base64_decode;
use function json_decode;

use const JSON_THROW_ON_ERROR;

class ForeignDomainTest extends AbstractDomainTest implements TestCaseInterface
{
    use ForeignDatabaseInjection;
    use RemoteCommandDispatcherInjection;

    protected string $prefix = 'foreign';

    protected function getPageToSiteBaseMapping(): array
    {
        $request = new RemoteCommandRequest();
        $request->setCommand(ShortSiteConfigurationCommand::IDENTIFIER);

        $response = $this->remoteCommandDispatcher->dispatch($request);

        if ($response->isSuccessful()) {
            $responseParts = GeneralUtility::trimExplode(':', $response->getOutputString());
            $base64encoded = $responseParts[1];
            $jsonEncoded = base64_decode($base64encoded);
            $shortSiteConfig = json_decode($jsonEncoded, true, 512, JSON_THROW_ON_ERROR);
        } else {
            throw new ForeignSiteConfigUnavailableException($response, 2038602953);
        }
        return array_combine(
            array_column($shortSiteConfig, 'rootPageId'),
            array_column($shortSiteConfig, 'base'),
        );
    }

    protected function getConnection(): Connection
    {
        return $this->foreignDatabase;
    }

    public function getDependencies(): array
    {
        return [
            ForeignDatabaseTest::class,
            RemoteAdapterTest::class,
        ];
    }
}
