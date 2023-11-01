<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Publisher;

use In2code\In2publishCore\Tests\UnitTestCase;
use ReflectionProperty;

abstract class AbstractFilesystemPublisherTest extends UnitTestCase
{
    protected function getRequestTokenFromPublisher($filesystemRecordPublisher)
    {
        $reflectionProperty = new ReflectionProperty($filesystemRecordPublisher, 'requestToken');
        $reflectionProperty->setAccessible(true);
        return $reflectionProperty->getValue($filesystemRecordPublisher);
    }
}
