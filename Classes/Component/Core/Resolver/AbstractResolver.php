<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Resolver;

use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\AbstractProcessor;
use TYPO3\CMS\Core\Core\Environment;

use function debug_backtrace;

abstract class AbstractResolver implements Resolver
{
    protected array $metaInfo = [];

    public function __construct()
    {
        if (Environment::getContext()->isDevelopment()) {
            $backtrace = debug_backtrace();
            foreach ($backtrace as $frame) {
                if (
                    isset($frame['object'])
                    && $frame['object'] instanceof AbstractProcessor
                    && 'buildResolver' === $frame['function']
                ) {
                    $this->metaInfo['builtBy'] = [
                        'class' => $frame['class'],
                        'args' => $frame['args'],
                    ];
                    break;
                }
            }
        }
    }

    /**
     * The information which processor built this resolver is only available
     * if the resolver was built by a processor which extends AbstractProcessor.
     * The method AbstractProcessor::buildResolver must be called.
     */
    public function getMetaInfo(): array
    {
        return $this->metaInfo;
    }
}
