<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Communication\RemoteCommandExecution;

use InvalidArgumentException;
use function array_values;
use function explode;
use function filter_var;
use function implode;
use function is_string;

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

/**
 * Class RemoteCommandResponse
 */
class RemoteCommandResponse
{
    /**
     * @var array
     */
    protected $output = [];

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var int
     */
    protected $exitStatus = 0;

    /**
     * RemoteCommandResponse constructor.
     *
     * @param array|string $output
     * @param array|string $errors
     * @param int $exitStatus
     */
    public function __construct($output = [], $errors = [], $exitStatus = 0)
    {
        $this->setOutput($output);
        $this->setErrors($errors);
        $this->setExitStatus($exitStatus);
    }

    /**
     * @return array
     */
    public function getOutput(): array
    {
        return $this->output;
    }

    /**
     * @return string
     */
    public function getOutputString(): string
    {
        return implode(PHP_EOL, $this->output);
    }

    /**
     * @param array|string $output
     */
    public function setOutput($output)
    {
        $this->output = $this->convertAndSanitizeResponse($output);
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param array|string $errors
     */
    public function setErrors($errors)
    {
        $this->errors = $this->convertAndSanitizeResponse($errors);
    }

    /**
     * @return string
     */
    public function getErrorsString(): string
    {
        return implode(PHP_EOL, $this->errors);
    }

    /**
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return 0 === $this->exitStatus;
    }

    /**
     * @return int
     */
    public function getExitStatus(): int
    {
        return $this->exitStatus;
    }

    /**
     * @param int $exitStatus
     */
    public function setExitStatus($exitStatus)
    {
        $this->exitStatus = (int)$exitStatus;
    }

    /**
     * @param array|string $response
     *
     * @return array
     */
    protected function convertAndSanitizeResponse($response): array
    {
        if (is_string($response)) {
            $response = explode("\n", $response);
        } elseif (!is_array($response)) {
            throw new InvalidArgumentException('Can not add output that is neither an array nor string', 1493829409);
        } else {
            // reset keys to represent line numbers
            $response = array_values($response);
        }
        $sanitized = [];
        foreach ($response as $row => $string) {
            $sanitized[(int)$row] = filter_var(
                $string,
                FILTER_SANITIZE_STRING,
                FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_NO_ENCODE_QUOTES
            );
        }
        return $sanitized;
    }
}
