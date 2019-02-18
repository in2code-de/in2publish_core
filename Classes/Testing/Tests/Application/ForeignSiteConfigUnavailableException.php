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

use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandResponse;
use In2code\In2publishCore\In2publishCoreException;

/**
 * Class ForeignSiteConfigUnavailableException
 */
class ForeignSiteConfigUnavailableException extends In2publishCoreException
{
    const MESSAGE = 'An error occurred during fetching the remote site configuration';
    const CODE = 1549900962;

    /**
     * @var string
     */
    protected $errorString = '';

    /**
     * @var string
     */
    protected $outputString = '';

    /**
     * @var int
     */
    protected $exitStatus = 0;

    /**
     * @param RemoteCommandResponse $response
     * @return ForeignSiteConfigUnavailableException
     */
    public static function fromFailedRceResponse(RemoteCommandResponse $response)
    {
        $self = new static(static::MESSAGE, self::CODE);
        $self->outputString = $response->getOutputString();
        $self->errorString = $response->getErrorsString();
        $self->exitStatus = $response->getExitStatus();
        return $self;
    }

    /**
     * @return string
     */
    public function getErrorString(): string
    {
        return $this->errorString;
    }

    /**
     * @return string
     */
    public function getOutputString(): string
    {
        return $this->outputString;
    }

    /**
     * @return int
     */
    public function getExitStatus(): int
    {
        return $this->exitStatus;
    }
}
