# PhysicalFileWasPublished

## When

Each time a record from `sys_file` was published and the PhysicalFilePublisher published the actual file on disk, this
event will be dispatched.

## What

* `record`: The `sys_file` record which was published.

## Possibilities

This event was created so it is possible to react on the publishing of actual files. You can collect all published files
during the publishing process and clear the caches of pages where these files are used (which is what the
FileEdgeCacheInvalidator essentially does).

### Example

This example event listener collects all file identifiers (the file name inside the FAL storage) of files which have
been published.

```php
<?php

use In2code\In2publishCore\Event\PhysicalFileWasPublished;

class PhysicalFilePublishingListener
{
    protected $messages = [];

    public function onPhysicalFileWasPublished(PhysicalFileWasPublished $event): void
    {
        $this->messages[] = $event->getRecord()->getLocalProperty('identifier') . ' was published';
    }
}

```
