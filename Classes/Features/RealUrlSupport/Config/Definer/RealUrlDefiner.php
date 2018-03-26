<?php

namespace In2code\In2publishCore\Features\RealUrlSupport\Config\Definer;

/***************************************************************
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
 ***************************************************************/

use In2code\In2publishCore\Config\Builder;
use In2code\In2publishCore\Config\Definer\DefinerInterface;
use In2code\In2publishCore\Config\Node\Node;
use In2code\In2publishCore\Config\Node\NodeCollection;

/**
 * Class RealUrlDefiner
 */
class RealUrlDefiner implements DefinerInterface
{
    /**
     * @return NodeCollection
     */
    public function getLocalDefinition()
    {
        return Builder::start()
                      ->addArray(
                          'tasks',
                          Builder::start()
                                 ->addArray(
                                     'realUrl',
                                     Builder::start()
                                            ->addArray(
                                                'excludedDokTypes',
                                                Builder::start()->addGenericScalar(Node::T_INTEGER, Node::T_INTEGER),
                                                [254]
                                            )
                                            ->addBoolean('requestFrontend', false)
                                 )
                      )
                      ->addArray(
                          'excludeRelatedTables',
                          Builder::start()->addGenericScalar(Node::T_INTEGER, Node::T_STRING),
                          [
                              'tx_realurl_pathdata',
                              'tx_realurl_uniqalias',
                              'tx_realurl_urldata',
                          ]
                      )
                      ->end();
    }

    /**
     * @return NodeCollection
     */
    public function getForeignDefinition()
    {
        return Builder::start()->end();
    }
}
