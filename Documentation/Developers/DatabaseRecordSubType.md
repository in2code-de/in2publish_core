# Create a `DatabaseRecord` subtype

There are some new basic types for records. `DatabaseRecord`, `MmDatabaseRecord`, `FileRecord`, and `FolderRecord`.
Additionally, there are some special record types that we neglect for the sake of simplicity. Each of these records
represents a single database row, file, or folder **on both local and foreign**. There is no "This sys_file record is
also a FAL file"-hybrid anymore. Also, there is a proper Factory for all of these records, which you must always use.

Yet, the case of `DatabaseRecord` is more complex. There are different types of data among the tables in each TYPO3 and
each table can have its very own logic, which the Content Publisher can not follow e.g. because there is not TCA. An
example of this would be the `tt_content` with `CType` `shortcut`. That kind of record requires that records which are
linked to it by the field `records` are published before the record itself can be published.

To enable other developers to implement their table-based logic on top of their data, it is possible to create
a `DatabaseRecord` subtype. Everything you require is a `XxxDatabaseRecordFactory` which
implements `\In2code\In2publishCore\Component\Core\Record\Factory\DatabaseRecordFactory` and the
corresponding `XxxDatabaseRecord` model.

Your new factory will be automatically registered by the interface it implements (don't forget to clear your DI caches!)
and if it has a higher priority then every other factory that can produce objects for the given table, it will be used.

## Example

```php
<?php
class NewsDatabaseRecord extends DatabaseRecord
{
}
```

```php
<?php

use In2code\In2publishCore\Component\Core\Record\Factory\DatabaseRecordFactory;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;

class NewsDatabaseRecordFactory implements DatabaseRecordFactory
{
    public function getPriority(): int
    {
        return 100;
    }

    public function isResponsible(string $table): bool
    {
        return 'tx_news_domain_model_news' === $table;
    }

    public function createDatabaseRecord(
        string $table,
        int $id,
        array $localProps,
        array $foreignProps,
        array $tableIgnoredFields
    ): DatabaseRecord {
        return new NewsDatabaseRecord($table, $id, $localProps, $foreignProps, $tableIgnoredFields);
    }
}
```
