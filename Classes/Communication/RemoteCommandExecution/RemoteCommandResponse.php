<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Communication\RemoteCommandExecution;

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

use InvalidArgumentException;

use function array_values;
use function explode;
use function htmlspecialchars;
use function implode;
use function is_array;
use function is_string;

use const ENT_NOQUOTES;
use const PHP_EOL;

class RemoteCommandResponse
{
    protected array $output = [];

    protected array $errors = [];

    protected int $exitStatus = 0;

    /**
     * RemoteCommandResponse constructor.
     *
     * @param array|string $output
     * @param array|string $errors
     * @param int $exitStatus
     */
    public function __construct($output = [], $errors = [], int $exitStatus = 0)
    {
        $this->setOutput($output);
        $this->setErrors($errors);
        $this->setExitStatus($exitStatus);
    }

    public function getOutput(): array
    {
        return $this->output;
    }

    public function getOutputString(): string
    {
        return implode(PHP_EOL, $this->output);
    }

    /**
     * @param array|string $output
     */
    public function setOutput($output): void
    {
        $this->output = $this->convertAndSanitizeResponse($output);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param array|string $errors
     */
    public function setErrors($errors): void
    {
        $this->errors = $this->convertAndSanitizeResponse($errors);
    }

    public function getErrorsString(): string
    {
        return implode(PHP_EOL, $this->errors);
    }

    public function isSuccessful(): bool
    {
        return 0 === $this->exitStatus;
    }

    public function getExitStatus(): int
    {
        return $this->exitStatus;
    }

    public function setExitStatus(int $exitStatus): void
    {
        $this->exitStatus = $exitStatus;
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
            $sanitized[$row] = htmlspecialchars($string, ENT_NOQUOTES);
        }
        return $sanitized;
    }
}
