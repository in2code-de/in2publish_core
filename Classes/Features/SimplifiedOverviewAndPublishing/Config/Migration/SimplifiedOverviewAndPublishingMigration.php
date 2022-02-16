<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\SimplifiedOverviewAndPublishing\Config\Migration;

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

use In2code\In2publishCore\Config\Migration\AbstractMigration;
use In2code\In2publishCore\Features\SimplifiedOverviewAndPublishing\ShallowRecordFinder;

use function sprintf;

class SimplifiedOverviewAndPublishingMigration extends AbstractMigration
{
    protected const MESSAGE = 'The setting %s was replaced with the new SimplifiedOverviewAndPublishing feature.  '
                              . 'The option was automatically migrated and removed from the configuration. ' .
                              'New configuration: factory.finder = ' . ShallowRecordFinder::class;

    public function migrate(array $config): array
    {
        $enable = false;
        if (
            isset($config['factory']['simpleOverviewAndAjax'])
            && $config['factory']['simpleOverviewAndAjax']
        ) {
            $this->addMessage(sprintf(self::MESSAGE, 'factory.simpleOverviewAndAjax'));
            $enable = true;
        }
        if (
            isset($config['features']['simplePublishing']['enable'])
            && $config['features']['simplePublishing']['enable']
        ) {
            $this->addMessage(sprintf(self::MESSAGE, 'features.simplePublishing.enable'));
            $enable = true;
        }
        if ($enable) {
            $config['factory']['finder'] = ShallowRecordFinder::class;
        }
        return $config;
    }
}
