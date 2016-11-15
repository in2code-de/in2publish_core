<?php

if (!($webRoot = getenv('TYPO3_PATH_WEB'))) {
    $webRoot = realpath(__DIR__ . '/../../../../') . '/';

    putenv('TYPO3_PATH_WEB=' . $webRoot);

    if (!file_exists($webRoot . 'vendor/autoload.php')) {
        throw new \LogicException(
            'Use this bootstrap file only when you installed in2publish via composer as dependency or predefine TYPO3_PATH_WEB'
        );
    }
}

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['in2publish_core'] = array();

$coreBootstrap = $webRoot . '/typo3/sysext/core/Build/UnitTestsBootstrap.php';

if (!file_exists($coreBootstrap)) {
    throw new \LogicException('Can not find core unit test bootstrap. Is TYPO3 installed correctly?');
}

require($coreBootstrap);

// manually add required PSR-4 namespaces to ensure they are loaded.
//$classLoader = \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->getEarlyInstance(\Composer\Autoload\ClassLoader::class);
//$classLoader->addPsr4(
//    'In2code\\In2publishCore\\Tests\\Unit\\',
//    __DIR__ . '/Unit/',
//    true
//);
//$classLoader->addPsr4(
//    'In2code\\In2publishCore\\Tests\\Helper\\',
//    __DIR__ . '/Helper/',
//    true
//);
//$classLoader->addPsr4(
//    'TYPO3\\CMS\\Core\\Tests\\',
//    $webRoot . '/typo3/sysext/core/Tests/',
//    true
//);
