# FolderInstanceWasCreated

Replaces the `\In2code\In2publishCore\Controller\FileController / folderInstanceCreated` Signal.

## When

1. Simple Files is not active (`features.simpleFiles.enable = FALSE`).
2. The record instance for the Publish Files Module was created.

## What

* `record`: The record which represents the selected folder. The record's related records are the file entries
  and sub folders of that folder.

## Possibilities

You can modify the record instance as you like. Add new related records, set different properties for a related record,
or access additional properties on the records. There are no boundaries except that you can not replace the record
object of this event.
