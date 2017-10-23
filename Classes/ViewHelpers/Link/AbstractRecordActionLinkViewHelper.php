<?php
namespace In2code\In2publishCore\ViewHelpers\Link;

/***************************************************************
 * Copyright notice
 *
 * (c) 2017 in2code.de and the following authors:
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

use In2code\In2publishCore\Domain\Model\RecordInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Class AbstractRecordActionLinkViewHelper
 */
abstract class AbstractRecordActionLinkViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    /**
     *
     */
    public function initializeArguments()
    {
        $this->registerArgument('record', RecordInterface::class, 'The record object the link is built for');
        $this->registerArgument('table', 'string', 'Alt. to record: The record table');
        $this->registerArgument('identifier', 'integer', 'Alt. to record: The record identifier');
        parent::initializeArguments();
        parent::registerUniversalTagAttributes();
    }

    /**
     * @return string
     */
    public function render()
    {
        if (!empty($this->arguments['record'])) {
            /** @var RecordInterface $record */
            $record = $this->arguments['record'];
            $table = $record->getTableName();
            $identifier = $record->getIdentifier();
        } else {
            $table = $this->arguments['table'];
            $identifier = $this->arguments['identifier'];
        }

        $uri = $this->buildUri($table, $identifier);
        $this->tag->addAttribute('href', $uri);
        $this->tag->setContent($this->renderChildren());
        return $this->tag->render();
    }

    /**
     * @param string $table
     * @param int $identifier
     */
    abstract protected function buildUri($table, $identifier);
}
