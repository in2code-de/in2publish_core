<?php

$GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'] = false;
$GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly'] = 2;

$GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = true;
$GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = true;
$GLOBALS['TYPO3_CONF_VARS']['LOG']['TYPO3']['CMS']['deprecations']['writerConfiguration']['notice']['TYPO3\CMS\Core\Log\Writer\FileWriter']['disabled'] = false;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] = 1;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors'] = 12290;
