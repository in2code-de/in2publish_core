# Record Extensions

Records are the heart of the Content Publisher, so it is sometimes required to add some functions to them. Since PHP
does not provide dynamic method injection, we've built an extension strategy right into the Record model itself.

Record extensions are written as PHP traits. An intermediate trait, which will use all extension traits, is used by the
Record (`AbstractRecord` to be precise). The intermediate trait is named `RecordExtensionTrait`.

## How to

You can see how easy it is to extend the record classes by having a look at the `RecordBreadcrumb` feature.

You need two things:

1. The `RecordExtensionTrait`
    ```php
    <?php

    use In2code\In2publishCore\Component\Core\Record\Model\Record;

    /**
     * @property Record[][] $children
     */
    trait ChildCountRecordExtension
    {
        public function getChildCount(): int
        {
            $count = 0;
            foreach ($this->children as $children) {
                foreach ($children as $child) {
                    $count++;
                }
            }
            return $count;
        }
    }
    ```
2. Your ExtensionsProvider which implements `RecordExtensionsProvider`
    ```php
    <?php

    use In2code\In2publishCore\Component\Core\DependencyInjection\RecordExtensionProvider\RecordExtensionsProvider;

    class ChildCountRecordExtensionsProvider implements RecordExtensionsProvider
    {
        public function getExtensions(): array
        {
            return [
                ChildCountRecordExtension::class,
            ];
        }
    }
    ```

Clear the Dependency Injection Caches and that's it. You can now execute your method on all records
like `$record->getChildCount();`.
