<?php
namespace In2code\In2publishCore\Domain\Factory;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 in2code.de
 *  Alex Kellner <alexander.kellner@in2code.de>,
 *  Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use In2code\In2publishCore\Domain\Model\Task\AbstractTask;

/**
 * converts database rows from tx_in2code_in2publish_task into Task objects
 */
class TaskFactory
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     * @inject
     */
    protected $objectManager = null;

    /**
     * @param array $taskProperties
     * @return AbstractTask
     */
    public function convertToObject(array $taskProperties)
    {
        $className = $taskProperties['task_type'];
        $configuration = json_decode($taskProperties['configuration'], true);

        /** @var AbstractTask $object */
        $object = $this->objectManager->get($className, $configuration, $taskProperties['uid']);
        $object->setCreationDate(new \DateTime($taskProperties['creation_date']));

        if ($taskProperties['messages']) {
            $object->setMessages(json_decode($taskProperties['messages'], true));
        }
        if ($taskProperties['execution_begin']) {
            $object->setExecutionBegin(new \DateTime($taskProperties['execution_begin']));
        }
        if ($taskProperties['execution_end']) {
            $object->setExecutionEnd(new \DateTime($taskProperties['execution_end']));
        }

        return $object;
    }
}
