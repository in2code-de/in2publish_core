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
    expectedReturnValues(
        \In2code\In2publishCore\Service\Context\ContextService::getContext(),
        \In2code\In2publishCore\Service\Context\ContextService::LOCAL,
        \In2code\In2publishCore\Service\Context\ContextService::FOREIGN
    );

    expectedArguments(
        \In2code\In2publishCore\Component\TcaHandling\PreProcessing\ProcessingResult::__construct(),
        0,
        \In2code\In2publishCore\Component\TcaHandling\PreProcessing\ProcessingResult::COMPATIBLE,
        \In2code\In2publishCore\Component\TcaHandling\PreProcessing\ProcessingResult::INCOMPATIBLE
    );
    expectedArguments(
        \In2code\In2publishCore\Component\RemoteProcedureCall\Envelope::__construct(),
        0,
        \In2code\In2publishCore\Component\RemoteProcedureCall\EnvelopeDispatcher::CMD_FOLDER_EXISTS,
        \In2code\In2publishCore\Component\RemoteProcedureCall\EnvelopeDispatcher::CMD_FILE_EXISTS,
        \In2code\In2publishCore\Component\RemoteProcedureCall\EnvelopeDispatcher::CMD_LIST_FOLDER_CONTENTS
    );
    expectedArguments(
        \In2code\In2publishCore\Component\ConfigContainer\Builder::addNode(),
        0,
        \In2code\In2publishCore\Component\ConfigContainer\Node\Node::T_ARRAY,
        \In2code\In2publishCore\Component\ConfigContainer\Node\Node::T_STRICT_ARRAY,
        \In2code\In2publishCore\Component\ConfigContainer\Node\Node::T_STRING,
        \In2code\In2publishCore\Component\ConfigContainer\Node\Node::T_OPTIONAL_ARRAY,
        \In2code\In2publishCore\Component\ConfigContainer\Node\Node::T_OPTIONAL_STRING,
        \In2code\In2publishCore\Component\ConfigContainer\Node\Node::T_INTEGER,
        \In2code\In2publishCore\Component\ConfigContainer\Node\Node::T_BOOLEAN
    );
    expectedArguments(
        \In2code\In2publishCore\Testing\Data\FalStorageTestSubjectsProvider::getStorages(),
        0,
        \In2code\In2publishCore\Testing\Data\FalStorageTestSubjectsProvider::PURPOSE_CASE_SENSITIVITY,
        \In2code\In2publishCore\Testing\Data\FalStorageTestSubjectsProvider::PURPOSE_DRIVER,
        \In2code\In2publishCore\Testing\Data\FalStorageTestSubjectsProvider::PURPOSE_MISSING,
        \In2code\In2publishCore\Testing\Data\FalStorageTestSubjectsProvider::PURPOSE_UNIQUE_TARGET
    );
}






