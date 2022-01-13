<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Config\Validator;

/*
 * Copyright notice
 *
 * (c) 2018 in2code.de and the following authors:
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

use In2code\In2publishCore\Config\ValidationContainer;

use function fclose;
use function fsockopen;
use function is_resource;

class HostNameValidator implements ValidatorInterface
{
    protected int $port;

    /**
     * @param int $port Set another port if the default one is actually used
     */
    public function __construct(int $port = 61252)
    {
        $this->port = $port;
    }

    /**
     * @param mixed $value
     * @SuppressWarnings(PHPMD.ErrorControlOperator) I don't remember the reason, but I had one. I swear.
     */
    public function validate(ValidationContainer $container, $value): void
    {
        $resource = @fsockopen($value, $this->port, $errorCode, $errorMessage, 1);
        if (false === $resource) {
            $container->addError("The host $value is not reachable: [$errorCode] $errorMessage");
        }
        if (is_resource($resource)) {
            fclose($resource);
        }
    }
}
