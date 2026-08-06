<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\TestingFramework\Core\Testbase;

call_user_func(function () {
    if (!getenv('IN2PUBLISH_CONTEXT')) {
        putenv('IN2PUBLISH_CONTEXT=Local');
    }
    $testbase = new Testbase();
    $testbase->defineOriginalRootPath();
    $testbase->createDirectory(ORIGINAL_ROOT . 'typo3temp/var/tests');
    $testbase->createDirectory(ORIGINAL_ROOT . 'typo3temp/var/transient');

    $GLOBALS['EXEC_TIME'] = time();
    $GLOBALS['ACCESS_TIME'] = $GLOBALS['EXEC_TIME'] - $GLOBALS['EXEC_TIME'] % 60;
    $GLOBALS['SIM_EXEC_TIME'] = $GLOBALS['EXEC_TIME'];
    $GLOBALS['SIM_ACCESS_TIME'] = $GLOBALS['ACCESS_TIME'];

    $context = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class);
    $context->setAspect('date', new DateTimeAspect(new \DateTimeImmutable('@' . $GLOBALS['EXEC_TIME'])));

    // PHPUnit resets $GLOBALS to this snapshot after every test because backupGlobals is enabled.
    // Destructors running during garbage collection or shutdown therefore see a configuration
    // without an encryption key, which makes HashService emit warnings and create invalid HMACs.
    $settingsFile = dirname($testbase->getPackagesPath()) . '/config/system/settings.php';
    if (file_exists($settingsFile)) {
        $settings = require $settingsFile;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = $settings['SYS']['encryptionKey'] ?? '';
    }
});
