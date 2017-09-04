<?php
namespace In2code\In2publishCore\Command;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 in2code.de
 *  Alex Kellner <alexander.kellner@in2code.de>,
 *  Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use In2code\In2publishCore\Service\Context\ContextService;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Class AbstractCommandController
 */
abstract class AbstractCommandController extends CommandController
{
    const EXIT_NO_CONTEXT = 210;
    const EXIT_WRONG_CONTEXT = 211;

    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * @var ContextService
     */
    protected $contextService = null;

    /**
     * AbstractCommandController constructor.
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->contextService = GeneralUtility::makeInstance(ContextService::class);
    }

    /**
     * Checks if the IN2PUBLISH_CONTEXT environment variable has been set
     */
    protected function callCommandMethod()
    {
        if (!$this->contextService->isContextDefined()) {
            $this->logger->error(
                'The command controller ' . static::class . ' was called over '
                . php_sapi_name() . ' without the IN2PUBLISH_CONTEXT environment variable'
            );
            $this->outputLine(
                'You have to specify a context before running this ("Local" or "Foreign"). Example: '
                . 'command like "IN2PUBLISH_CONTEXT=Local ./typo3/cli_dispatch.phpsh extbase help"'
            );
            $this->sendAndExit(static::EXIT_NO_CONTEXT);
        }
        parent::callCommandMethod();
    }
}
