<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Command\Foreign\Status;

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

use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use const PHP_EOL;

class DbInitQueryEncodedCommandTest extends UnitTestCase
{
    public function testCommandCanBeExecuted(): void
    {
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $command = new DbInitQueryEncodedCommand();

        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['initCommands'] = 'foo foo bar bar';

        $code = $command->run($input, $output);

        $this->assertSame(0, $code);
        $this->assertSame('DBinit: ImZvbyBmb28gYmFyIGJhciI=' . PHP_EOL, $output->fetch());
    }
}
