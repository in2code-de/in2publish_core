<?php
namespace In2code\In2publishCore\Testing\Tests;

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

use In2code\In2publishCore\Testing\Utility\TestLabelLocalizer;

/**
 * Class TestResult
 */
class TestResult
{
    const OK = 'ok';
    const WARNING = 'warning';
    const ERROR = 'error';
    const SKIPPED = 'notice';

    /**
     * @var string
     */
    protected $severity = self::OK;

    /**
     * @var string
     */
    protected $label = '';

    /**
     * @var string
     */
    protected $messages = '';

    /**
     * @var array|null
     */
    protected $labelArguments = null;

    /**
     * Error constructor.
     *
     * @param string $label Key of the label for headline. Only used when $severity !== OK
     * @param string $severity
     * @param array $messages Keys of labels for explanations what went wrong.
     * @param array|null $labelArguments
     * @internal param array|null $arguments
     */
    public function __construct(
        $label,
        $severity = self::OK,
        array $messages = array(),
        array $labelArguments = null
    ) {
        $this->severity = $severity;
        $this->label = $label;
        $this->messages = $messages;
        $this->labelArguments = $labelArguments;
    }

    /**
     * @return string
     */
    public function getSeverity()
    {
        return $this->severity;
    }

    /**
     * @param string $severity
     */
    public function setSeverity($severity)
    {
        $this->severity = $severity;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function getTranslatedLabel()
    {
        return TestLabelLocalizer::translate($this->label, $this->labelArguments);
    }

    /**
     * @return string
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param string $messages
     */
    public function setMessages($messages)
    {
        $this->messages = $messages;
    }

    /**
     * @return string
     */
    public function getTranslatedMessages()
    {
        $translatedMessages = array();
        foreach ($this->messages as $message) {
            $translatedMessages[] = TestLabelLocalizer::translate($message);
        }
        return implode(PHP_EOL, $translatedMessages);
    }
}
