<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\RemoteProcedureCall;

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

use LogicException;

use function array_filter;

class Envelope
{
    protected int $uid = 0;
    protected string $command = '';
    protected array $request = [];
    /** @var mixed */
    protected $response = '';

    /**
     * @param string $command
     * @param array $request
     * @param mixed $response
     * @param int|null $uid
     */
    public function __construct(string $command, array $request = [], $response = null, int $uid = null)
    {
        $this->setCommand($command);
        $this->setRequest($request);
        if (null !== $response) {
            $this->setResponse($response);
        }
        if (null !== $uid) {
            $this->setUid($uid);
        }
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function setUid(int $uid): void
    {
        if (0 !== $this->uid) {
            throw new LogicException('Can not overrule an envelope\'s uid', 1474386795);
        }
        $this->uid = $uid;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function setCommand(string $command): void
    {
        if ('' !== $this->command) {
            throw new LogicException('Can not overrule an envelope\'s command', 1474386882);
        }
        $this->command = $command;
    }

    public function getRequest(): array
    {
        return $this->request;
    }

    /**
     * @param mixed $request
     */
    public function setRequest(array $request): void
    {
        if ([] !== $this->request) {
            throw new LogicException('Can not overrule an envelope\'s request', 1474386975);
        }
        $this->request = $request;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param mixed $response
     */
    public function setResponse($response): void
    {
        if ('' !== $this->response) {
            throw new LogicException('Can not overrule an envelope\'s response', 1474386986);
        }
        $this->response = $response;
    }

    public function toArray(): array
    {
        return array_filter(
            [
                'uid' => $this->uid,
                'command' => $this->command,
                'request' => $this->request,
                'response' => $this->response,
            ],
        );
    }

    public static function fromArray(array $values): Envelope
    {
        $object = new Envelope('', []);
        foreach (['uid', 'command', 'request', 'response'] as $property) {
            if (isset($values[$property])) {
                $object->{$property} = $values[$property];
            }
        }
        return $object;
    }

    public function __toString(): string
    {
        return 'envelope:' . $this->uid;
    }
}
