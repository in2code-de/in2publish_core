# Known Issues

## Publish Files

**Problem**: If an editor deletes a file and uploads a file with same name in the same path,
the publisher shows two entries in the Publish Files module

**Solution**: Rename the file in the stage system, in order to be able to perform the deletion
process. Then publish the renamed file (or rename it again).

## TYPO3 v9 Preview & Compare URLs

The sites configuration introduced in TYPO3 v9 is not yet fully supported when building URLs for the overview module.
Building frontend links in the backend has always been an annoying task, but with the introduction of the sites config it just went worse.

## TYPO3 v9 Publish Overview translated pages

When selecting a page in the page tree the publish overview will show everything as expected, except for translated pages.
In TYPO3 9.0 pages_language_overlay was removed and replaced by pages with a sys_language_uid. This is not yet supported.

## TYPO3 v9 pages sorting detection partially broken

In TYPO3 v9 and up page sorting is sometimes not detected correctly.
Pages in the Publisher Overview module will not be marked changed/moved.

## TYPO3 v10 redirects are not publishable

The TYPO3 core extension `typo3/cms-redirects` makes everything completely different than the standard core-way, which
makes it technically impossible to publish `redirect`-Records without violating at least a dozen rules, the requirement
to implement a new backend module and new complex relation resolving strategies.
(one of the rules is, that the content publisher will never alter data during publishing to minimize errors and make
debugging easier)
We are aware of this issue and its implications. We are already in contact with the core team to find a solution to this
problem.

Technical info: Redirects do not have a TCA, they are not persisted using the DataHandler when changing a page slug and
they are persisted with an instance identifier which is different for the foreign side.

## File Preview URLs in Publish Files Module broken for non-public file storages

File Preview URLs rendered for files on Foreign are broken, if the file storage is not marked as public.

Reason: https://forge.typo3.org/issues/90330

## Context Menu Publishing

The context menu entry must be created very quickly to not degrade the overall context menu performance.
This means, that the context menu entry does not fully know about all conditions which have to be met for the record
to be publishable.
The context menu is created, when the current user is allowed to publish and 3rd party integrations allow the publishing
of this page. The context menu entry does not take into account if the record is actually changed. Publishing a page
which has no changes will result in the message "The "
