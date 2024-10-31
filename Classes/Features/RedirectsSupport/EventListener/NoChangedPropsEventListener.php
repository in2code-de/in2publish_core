<?php

namespace In2code\In2publishCore\Features\RedirectsSupport\EventListener;

use In2code\In2publishCore\Component\Core\Reason\Reason;
use In2code\In2publishCore\Event\CollectReasonsWhyTheRecordIsNotPublishable;

class NoChangedPropsEventListener
{

    public function checkChangedProps(CollectReasonsWhyTheRecordIsNotPublishable $event): void
    {
        $record = $event->getRecord();
        if ('sys_redirect' === $record->getClassification()) {
            $changedProps = $record->getChangedProps();
            // if there is only one changed prop and its value is 'source_host', we can ignore it
            if (count($changedProps) === 1 && $changedProps[0] === 'source_host') {
                $event->addReason(new Reason('The only changed prop is "source_host"'));
            }
        }
    }

}