<?php


namespace In2code\In2publishCore\Features\PublishSorting\Config\Definer;


use In2code\In2publishCore\Config\Builder;
use In2code\In2publishCore\Config\Definer\DefinerInterface;
use In2code\In2publishCore\Config\Node\NodeCollection;

class PublishSortingDefiner implements DefinerInterface
{
    public function getLocalDefinition(): NodeCollection
    {
        return Builder::start()
            ->addArray(
                'features',
                Builder::start()
                    ->addArray(
                        'publishSorting',
                        Builder::start()
                            ->addBoolean('enabled', true)
                    )
            )
            ->end();
    }

    public function getForeignDefinition(): NodeCollection
    {
        return Builder::start()->end();
    }
}
