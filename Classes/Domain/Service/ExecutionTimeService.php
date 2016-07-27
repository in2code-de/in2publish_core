<?php
namespace In2code\In2publishCore\Domain\Service;

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class ExecutionTimeService
 *
 * @package In2code\In2publish\Domain\Service
 */
class ExecutionTimeService implements SingletonInterface
{
    /**
     * @var float
     */
    protected $starttime = 0.00;

    /**
     * @var float
     */
    protected $executionTime = 0.00;

    /**
     * Set current microtime
     */
    public function start()
    {
        $this->starttime = -microtime(true);
    }

    public function getExecutionTime()
    {
        $this->stop();
        return $this->executionTime;
    }

    /**
     * Calculates and sets delta
     */
    protected function stop()
    {
        if ($this->starttime < 0) {
            $this->executionTime = $this->starttime + microtime(true);
        }
    }
}
