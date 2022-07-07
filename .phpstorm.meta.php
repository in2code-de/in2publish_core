<?php
namespace PHPSTORM_META {
    expectedReturnValues(
        \In2code\In2publishCore\Domain\Model\Record::getState(),
        \In2code\In2publishCore\Domain\Model\Record::S_ADDED,
        \In2code\In2publishCore\Domain\Model\Record::S_CHANGED,
        \In2code\In2publishCore\Domain\Model\Record::S_MOVED,
        \In2code\In2publishCore\Domain\Model\Record::S_SOFT_DELETED,
        \In2code\In2publishCore\Domain\Model\Record::S_DELETED,
        \In2code\In2publishCore\Domain\Model\Record::S_UNCHANGED
    );
    expectedReturnValues(
        \In2code\In2publishCore\Domain\Model\AbstractRecord::getState(),
        \In2code\In2publishCore\Domain\Model\Record::S_ADDED,
        \In2code\In2publishCore\Domain\Model\Record::S_CHANGED,
        \In2code\In2publishCore\Domain\Model\Record::S_MOVED,
        \In2code\In2publishCore\Domain\Model\Record::S_SOFT_DELETED,
        \In2code\In2publishCore\Domain\Model\Record::S_DELETED,
        \In2code\In2publishCore\Domain\Model\Record::S_UNCHANGED
    );

    expectedArguments(
        \In2code\In2publishCore\Component\TcaHandling\PreProcessing\ProcessingResult::__construct(),
        0,
        \In2code\In2publishCore\Component\TcaHandling\PreProcessing\ProcessingResult::COMPATIBLE,
        \In2code\In2publishCore\Component\TcaHandling\PreProcessing\ProcessingResult::INCOMPATIBLE
    );
}
