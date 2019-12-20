<?php
if (empty($webRoot = getenv('TYPO3_PATH_WEB'))) {
    putenv('TYPO3_PATH_WEB=' . $webRoot = realpath(__DIR__ . '/../../../../') . '/');
} else {
    $webRoot = rtrim($webRoot, '/') . '/';
}

if (!file_exists($autoload = $webRoot . 'vendor/autoload.php')
    && !file_exists($autoload = $webRoot . '../vendor/autoload.php')
) {
    throw new LogicException(
        'Use this bootstrap file only if you installed in2publish as composer dependency or predefined TYPO3_PATH_WEB'
    );
}

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['in2publish_core'] = [];

require($autoload);

$coreBootstrap = $webRoot . '/typo3/sysext/core/Build/UnitTestsBootstrap.php';

if (!file_exists($coreBootstrap)) {
    throw new LogicException('Can not find core unit test bootstrap. Is TYPO3 installed correctly?');
}

require($coreBootstrap);
