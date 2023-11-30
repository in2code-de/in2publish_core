# Known Issues

## File Preview URLs in Publish Files Module are not shown when using EXT:fal_securedownload

File Preview URLs are not rendered in the Publish Files Module, if EXT:fal_securedownload is installed.
However, the functionality of EXT:fal_securedownload and file publishing is not affected.

## File Preview URLs in Publish Files Module broken for non-public file storages

File Preview URLs rendered for files on Foreign are broken, if the file storage is not marked as public.

Reason: https://forge.typo3.org/issues/90330

## Context Menu Publishing

The context menu entry must be created very quickly to not degrade the overall context menu performance. This means,
that the context menu entry does not know about all conditions which have to be met for the record to be publishable.

The context menu is created if the current user is allowed to publish and 3rd party integrations allow the publishing of
this page. The context menu entry does not take into account if the record is actually changed. Publishing a page which
has no changes will result in the message "This record is not yet publishable".
