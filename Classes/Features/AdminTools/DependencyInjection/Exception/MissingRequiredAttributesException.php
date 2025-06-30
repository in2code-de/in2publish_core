<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\AdminTools\DependencyInjection\Exception;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
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

use In2code\In2publishCore\In2publishCoreException;
use Throwable;

use function count;
use function implode;
use function sprintf;

/**
 * @codeCoverageIgnore
 */
class MissingRequiredAttributesException extends In2publishCoreException
{
    public const CODE = 1636557893;
    private const MESSAGE_1 = 'The service %s defines the tag "%s" which requires the attributes %s to be defined, but the attribute %s is missing';
    private const MESSAGE_N = 'The service %s defines the tag "%s" which requires the attributes %s to be defined, but the attributes %s are missing';
    protected string $serviceName;
    protected string $tagName;
    /** @var array<string> */
    protected array $requiredAttributes;
    /** @var array<string> */
    protected array $missingRequiredKeys;

    public function __construct(
        string $serviceName,
        string $tagName,
        array $requiredAttributes,
        array $missingRequiredKeys,
        ?Throwable $previous = null
    ) {
        $this->serviceName = $serviceName;
        $this->tagName = $tagName;
        $this->requiredAttributes = $requiredAttributes;
        $this->missingRequiredKeys = $missingRequiredKeys;
        parent::__construct($this->composeMessage(), self::CODE, $previous);
    }

    public function getTagName(): string
    {
        return $this->tagName;
    }

    /** @return array<string> */
    public function getRequiredAttributes(): array
    {
        return $this->requiredAttributes;
    }

    /** @return array<string> */
    public function getMissingRequiredKeys(): array
    {
        return $this->missingRequiredKeys;
    }

    private function composeMessage(): string
    {
        $requiredList = implode(', ', $this->requiredAttributes);
        $missingList = implode(', ', $this->missingRequiredKeys);

        if (1 === count($this->missingRequiredKeys)) {
            return sprintf(self::MESSAGE_1, $this->serviceName, $this->tagName, $requiredList, $missingList);
        }

        return sprintf(self::MESSAGE_N, $this->serviceName, $this->tagName, $requiredList, $missingList);
    }
}
