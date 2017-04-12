<?php
namespace In2code\In2publishCore\Domain\Driver\Rpc;

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

/**
 * Class Envelope
 */
class Envelope
{
    /**
     * @var int
     */
    protected $uid = 0;

    /**
     * @var string
     */
    protected $command = '';

    /**
     * @var string
     */
    protected $request = '';

    /**
     * @var string
     */
    protected $response = '';

    /**
     * Envelope constructor.
     *
     * @param string $command
     * @param array $request
     * @param mixed $response
     * @param int|null $uid
     */
    public function __construct($command, array $request = array(), $response = null, $uid = null)
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

    /**
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param int $uid
     */
    public function setUid($uid)
    {
        if (0 !== $this->uid) {
            throw new \LogicException('Can not overrule an envelope\'s uid', 1474386795);
        }
        $this->uid = (int)$uid;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @param string $command
     */
    public function setCommand($command)
    {
        if ('' !== $this->command) {
            throw new \LogicException('Can not overrule an envelope\'s command', 1474386882);
        }
        $this->command = (string)$command;
    }

    /**
     * @return mixed
     */
    public function getRequest()
    {
        return unserialize($this->request);
    }

    /**
     * @param mixed $request
     */
    public function setRequest(array $request)
    {
        if ('' !== $this->request) {
            throw new \LogicException('Can not overrule an envelope\'s request', 1474386975);
        }
        $this->request = serialize($request);
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return unserialize($this->response);
    }

    /**
     * @param mixed $response
     */
    public function setResponse($response)
    {
        if ('' !== $this->response) {
            throw new \LogicException('Can not overrule an envelope\'s response', 1474386986);
        }
        $this->response = serialize($response);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array_filter(
            array(
                'uid' => $this->uid,
                'command' => $this->command,
                'request' => $this->request,
                'response' => $this->response,
            )
        );
    }

    /**
     * @param array $values
     * @return Envelope
     */
    public static function fromArray(array $values)
    {
        $object = new Envelope('', array());
        foreach (array('uid', 'command', 'request', 'response') as $property) {
            if (isset($values[$property])) {
                $object->{$property} = $values[$property];
            }
        }
        return $object;
    }
}
