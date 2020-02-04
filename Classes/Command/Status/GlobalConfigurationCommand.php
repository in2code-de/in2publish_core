<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Command\Status;

/*
 * Copyright notice
 *
 * (c) 2019 in2code.de and the following authors:
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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GlobalConfigurationCommand extends Command
{
    public const DESCRIPTION = 'Prints global configuration values';
    public const IDENTIFIER = 'in2publish_core:status:globalconfiguration';

    protected function configure()
    {
        $this->setDescription(self::DESCRIPTION)
             ->setHidden(true);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $utf8fileSystem = empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'])
            ? 'empty'
            : $GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'];
        $output->writeln('Utf8Filesystem: ' . $utf8fileSystem);
        $output->writeln('adminOnly: ' . ($GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly'] ?? 'empty'));
        return 0;
    }
}
