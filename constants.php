<?php

declare(strict_types=1);

namespace In2code\In2publishCore;

use TYPO3\CMS\Core\Information\Typo3Version;

use function define;
use function defined;

(static function (): void {
    $typo3Version = new Typo3Version();
    $majorVersion = $typo3Version->getMajorVersion();

    if (!defined('In2code\In2publishCore\TYPO3_V11')) {
        define('In2code\In2publishCore\TYPO3_V11', 11 === $majorVersion);
    }
    if (!(defined('In2code\In2publishCore\TYPO3_V12'))) {
        define('In2code\In2publishCore\TYPO3_V12', 12 === $majorVersion);
    }
})();
