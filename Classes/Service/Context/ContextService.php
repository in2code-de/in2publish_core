<?php
namespace In2code\In2publishCore\Service\Context;

/***************************************************************
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
 ***************************************************************/

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ContextService
 */
class ContextService implements SingletonInterface
{
    const LOCAL = 'Local';
    const FOREIGN = 'Foreign';
    const ENV_VAR_NAME = 'IN2PUBLISH_CONTEXT';

    /**
     * @var string
     */
    protected $context = self::FOREIGN;

    /**
     * Determines and stores the current context
     */
    public function __construct()
    {
        $this->context = $this->determineContext();
    }

    /**
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return string
     */
    protected function determineContext()
    {
        if (false === ($environmentVariable = getenv(self::ENV_VAR_NAME))) {
            return static::FOREIGN;
        } elseif (in_array($environmentVariable, array(static::LOCAL, static::FOREIGN))) {
            return $environmentVariable;
        } elseif (GeneralUtility::getApplicationContext()->isProduction()) {
            return static::FOREIGN;
        } else {
            throw new \LogicException('The defined in2publish context is not supported', 1469717011);
        }
    }

    /**
     * @return bool
     */
    public function isForeign()
    {
        return self::FOREIGN === $this->context;
    }

    /**
     * @return bool
     */
    public function isLocal()
    {
        return self::LOCAL === $this->context;
    }
}
