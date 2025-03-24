<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\FolderProcessor;
use In2code\In2publishCore\Component\Core\Resolver\GroupSingleTableResolver;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Container;

class FolderProcessorTest extends UnitTestCase
{
    public function testGroupSingleTableResolver(): void
    {
        $column = 'column_foo';
        $table = 'sys_file_reference';
        $tca = [
            'type' => 'folder',
        ];


        $resolver = $this->createMock(GroupSingleTableResolver::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($resolver);

        $resolver->expects($this->once())
            ->method('configure')
            ->with($table, $column);

        $folderProcessor = new FolderProcessor($container);
        $processingResult = $folderProcessor->process($table, $column, $tca);
        $this->assertTrue($processingResult->isCompatible());
        $this->assertInstanceOf(GroupSingleTableResolver::class, $resolver);
    }

}