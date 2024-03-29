<?php

namespace PHPSTORM_META {

    registerArgumentsSet('side', 'local', 'foreign');
    registerArgumentsSet(
        'DependencyRequirement',
        \In2code\In2publishCore\Component\Core\Record\Model\Dependency::REQ_EXISTING,
        \In2code\In2publishCore\Component\Core\Record\Model\Dependency::REQ_ENABLECOLUMNS,
        \In2code\In2publishCore\Component\Core\Record\Model\Dependency::REQ_FULL_PUBLISHED,
    );

    expectedReturnValues(
        \In2code\In2publishCore\Component\Core\Record\Model\Record::getState(),
        \In2code\In2publishCore\Component\Core\Record\Model\Record::S_ADDED,
        \In2code\In2publishCore\Component\Core\Record\Model\Record::S_CHANGED,
        \In2code\In2publishCore\Component\Core\Record\Model\Record::S_MOVED,
        \In2code\In2publishCore\Component\Core\Record\Model\Record::S_SOFT_DELETED,
        \In2code\In2publishCore\Component\Core\Record\Model\Record::S_DELETED,
        \In2code\In2publishCore\Component\Core\Record\Model\Record::S_UNCHANGED,
    );
    expectedReturnValues(
        \In2code\In2publishCore\Service\Context\ContextService::getContext(),
        \In2code\In2publishCore\Service\Context\ContextService::LOCAL,
        \In2code\In2publishCore\Service\Context\ContextService::FOREIGN,
    );
    expectedReturnValues(
        \In2code\In2publishCore\Component\Core\Record\Model\Dependency::getRequirement(),
        argumentsSet('DependencyRequirement'),
    );

    override(\Psr\Container\ContainerInterface::get(), type(0));
    override(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(), type(0));
    override(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceForDi(), type(0));

    expectedArguments(
        \In2code\In2publishCore\Component\Core\PreProcessing\ProcessingResult::__construct(),
        0,
        \In2code\In2publishCore\Component\Core\PreProcessing\ProcessingResult::COMPATIBLE,
        \In2code\In2publishCore\Component\Core\PreProcessing\ProcessingResult::INCOMPATIBLE,
    );
    expectedArguments(
        \In2code\In2publishCore\Component\RemoteProcedureCall\Envelope::__construct(),
        0,
        \In2code\In2publishCore\Component\RemoteProcedureCall\EnvelopeDispatcher::CMD_GET_FOLDER_INFO,
        \In2code\In2publishCore\Component\RemoteProcedureCall\EnvelopeDispatcher::CMD_GET_FILE_INFO,
        \In2code\In2publishCore\Component\RemoteProcedureCall\EnvelopeDispatcher::CMD_FILE_EXISTS,
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
        \In2code\In2publishCore\Component\ConfigContainer\Node\Node::T_BOOLEAN,
    );
    expectedArguments(
        \In2code\In2publishCore\Testing\Data\FalStorageTestSubjectsProvider::getStorages(),
        0,
        \In2code\In2publishCore\Testing\Data\FalStorageTestSubjectsProvider::PURPOSE_CASE_SENSITIVITY,
        \In2code\In2publishCore\Testing\Data\FalStorageTestSubjectsProvider::PURPOSE_DRIVER,
        \In2code\In2publishCore\Testing\Data\FalStorageTestSubjectsProvider::PURPOSE_MISSING,
        \In2code\In2publishCore\Testing\Data\FalStorageTestSubjectsProvider::PURPOSE_UNIQUE_TARGET,
    );
    expectedArguments(
        \In2code\In2publishCore\Service\Database\RawRecordService::getRawRecord(),
        2,
        argumentsSet('side'),
    );
    expectedArguments(
        \In2code\In2publishCore\Service\Database\RawRecordService::fetchRecord(),
        2,
        argumentsSet('side'),
    );
    expectedArguments(
        \In2code\In2publishCore\Component\Core\Record\Model\Dependency::__construct(),
        3,
        argumentsSet('DependencyRequirement'),
    );
}
