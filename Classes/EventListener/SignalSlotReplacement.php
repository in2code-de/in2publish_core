<?php

declare(strict_types=1);

namespace In2code\In2publishCore\EventListener;

use In2code\In2publishCore\Controller\FileController;
use In2code\In2publishCore\Event\FolderInstanceWasCreated;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException;

class SignalSlotReplacement
{
    /** @var Dispatcher */
    protected $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function onFolderInstanceWasCreated(FolderInstanceWasCreated $event): void
    {
        try {
            $this->dispatcher->dispatch(FileController::class, 'folderInstanceCreated', [$event->getRecord()]);
        } catch (InvalidSlotException $e) {
        } catch (InvalidSlotReturnException $e) {
        }
    }
}
